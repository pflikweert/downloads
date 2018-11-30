<?php

namespace TradusBundle\Repository;

use Doctrine\ORM\EntityRepository;
use TradusBundle\Entity\TradusUser;

/**
 * Class TradusUserRepository
 *
 * @package TradusBundle\Repository
 */
class TradusUserRepository extends EntityRepository {

    /**
     * @param string $email
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByEmail(string $email) 
    {
       $tradusUser = $this->createQueryBuilder('tradus_users')
            ->select('tradus_users')
            ->where('tradus_users.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();

        return $tradusUser;
    }

    /**
     * @param string $code
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByConfirmationToken(string $code) 
    {
       $tradusUser = $this->createQueryBuilder('tradus_users')
            ->select('tradus_users')
            ->where('tradus_users.confirmation_token = :code')
            ->andWhere('tradus_users.status = :status')
            ->setParameter('code', $code)
            ->setParameter('status', TradusUser::STATUS_PENDING)
            ->getQuery()
            ->getOneOrNullResult();

        return $tradusUser;
    }
}