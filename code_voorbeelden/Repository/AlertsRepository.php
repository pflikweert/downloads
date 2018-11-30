<?php

namespace TradusBundle\Repository;

use Doctrine\ORM\EntityRepository;
use TradusBundle\Entity\Alerts;
use TradusBundle\Entity\TradusUser;

/**
 * Class AlertsRepository
 *
 * @package TradusBundle\Repository
 */
class AlertsRepository extends EntityRepository {

    /**
     * @param TradusUser $user
     * @param int $ruleType
     * @param string $ruleIdentifier
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findExistingRule(TradusUser $user, int $ruleType, string $ruleIdentifier) {
        $alert = $this->createQueryBuilder('alerts')
            ->select('alerts')
            ->where('alerts.rule_identifier = :rule_identifier')
            ->andWhere('alerts.user = :user_id')
            ->andWhere('alerts.rule_type = :rule_type')
            ->setParameter('user_id', $user->getId())
            ->setParameter('rule_type', $ruleType)
            ->setParameter('rule_identifier', $ruleIdentifier)
            ->getQuery()
            ->getOneOrNullResult();

        return $alert;
    }

    /**
     * @param int $ruleType
     * @param \DateTime $createdAt
     * @param \DateTime $lastSendAt
     * @return mixed
     */
    public function findAllForSendingUpdate(int $ruleType, \DateTime $createdAt, \DateTime $lastSendAt) {
        $query = $this->createQueryBuilder('alerts')
            ->select('alerts')
            ->where('alerts.status = :status')
            ->andWhere('alerts.rule_type = :rule_type')
            ->andWhere('(
                (alerts.last_send_at IS NULL AND alerts.created_at <= :created_at) 
                 OR 
                (alerts.last_send_at IS NOT NULL AND alerts.last_send_at <= :last_send_at)
             )')
            ->setParameter('created_at', $createdAt)
            ->setParameter('last_send_at', $lastSendAt)
            ->setParameter('rule_type', $ruleType)
            ->setParameter('status', Alerts::STATUS_ACTIVE)
            ->getQuery();

        $alerts = $query->getResult();

        return $alerts;
    }


    /**
     * @param int $userId
     * @param \DateTime $startCountDate
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function countAlertsSend(int $userId, \DateTime $startCountDate) {
        $connection = $this->getEntityManager()->getConnection();
        $sql = 'SELECT count(*) as count FROM alerts 
                WHERE user_id = :user_id AND `status` = :status AND last_send_at >= :start_count_date;';

        $stmt = $connection->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'start_count_date' => $startCountDate->format("Y-m-d H:i:s"),
            'status' => Alerts::STATUS_ACTIVE,
        ]);

        // returns an array of arrays (i.e. a raw data set)
        $result = $stmt->fetchAll();

        return $result[0]['count'];
    }
}