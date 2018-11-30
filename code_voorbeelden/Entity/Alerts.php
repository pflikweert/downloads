<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use TradusBundle\Entity\TradusUser;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Alerts
 *
 * @ORM\Table(name="alerts", indexes={
 *          @ORM\Index(name="user_idx", columns={"user_id", "last_send_at", "status"}),
 *          @ORM\Index(name="last_send_at_idx", columns={"last_send_at", "status", "user_id"}),
 *          @ORM\Index(name="created_at_idx", columns={"created_at", "status", "user_id"}),
 *          @ORM\Index(name="updated_at_idx", columns={"updated_at", "status", "user_id"}),
 *     })
 *
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\AlertsRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Alerts {

    const STATUS_ACTIVE      = 100;
    const STATUS_DEACTIVATED = -10;
    const STATUS_DELETED     = -200;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\TradusUser", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * @Assert\NotBlank(message = "User can not be blank")
     * @Exclude
     */
    private $user;

    /**
     * @var int
     * @ORM\Column(name="rule_type", type="integer")
     * @Assert\Type("integer")
     */
    private $rule_type;

    /**
     * @var string
     * @ORM\Column(name="rule", type="text", nullable=false)
     * @Assert\Type("string")
     */
    private $rule;

    /**
     * @var string
     * @ORM\Column(name="rule_identifier", type="text", nullable=false)
     * @Assert\Type("string")
     */
    private $rule_identifier;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     * @Assert\Type("integer")
     * @Assert\Choice(callback={"TradusBundle\Entity\Alerts", "getValidStatusList"}, strict=true)
     */
    private $status = self::STATUS_ACTIVE;

   /**
    * @var \DateTime
    *
    * @Gedmo\Timestampable(on="create")
    * @ORM\Column(name="created_at", type="datetime")
    * @Assert\DateTime()
    */
    private $created_at;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     * @Assert\DateTime()
     *
     */
    private $updated_at;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_send_at", type="datetime", nullable=true)
     * @Assert\DateTime()
     *
     */
    private $last_send_at;

    /**
     * @var int
     *
     * @ORM\Column(name="updates_send", type="integer", nullable=true)
     * Assert\Type("integer")
     */
    private $updates_send = 0;

    /**
     * @param int $count
     */
    public function setUpdatesSend(int $count) {
        $this->updates_send = $count;
    }

    /**
     * @return int
     */
    public function getUpdatesSend() {
        return $this->updates_send;
    }

    /**
     * increase the updates send counter with 1
     */
    public function increaseUpdatesSend() {
        if ($this->updates_send == null)
            $this->updates_send = 0;
        $this->updates_send++;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void {
        $this->id = $id;
    }


    /**
     * @return TradusUser|mixed
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user): void {
        $this->user = $user;
    }

    /**
     * @return int
     */
    public function getRuleType(): int {
        return $this->rule_type;
    }

    /**
     * @param int $rule_type
     */
    public function setRuleType(int $rule_type): void {
        $this->rule_type = $rule_type;
    }

    /**
     * @return string
     */
    public function getRule(): string {
        return $this->rule;
    }

    /**
     * @param string $rule
     */
    public function setRule(string $rule): void {
        $this->rule = $rule;
    }

    /**
     * @return string
     */
    public function getRuleIdentifier(): string {
        return $this->rule_identifier;
    }

    /**
     * @param string $rule_identifier
     */
    public function setRuleIdentifier(string $rule_identifier): void {
        $this->rule_identifier = $rule_identifier;
    }

    /**
     * @return int
     */
    public function getStatus(): int {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void {
        $this->status = $status;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime {
        return $this->created_at;
    }

    /**
     * @param \DateTime $created_at
     */
    public function setCreatedAt(\DateTime $created_at): void {
        $this->created_at = $created_at;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime {
        return $this->updated_at;
    }

    /**
     * @param \DateTime $updated_at
     */
    public function setUpdatedAt(\DateTime $updated_at): void {
        $this->updated_at = $updated_at;
    }

    /**
     * @return \DateTime | null
     */
    public function getLastSendAt() {
        return $this->last_send_at;
    }

    /**
     * @param \DateTime $last_send_at
     */
    public function setLastSendAt(\DateTime $last_send_at): void {
        $this->last_send_at = $last_send_at;
    }

    public function __construct() {
        $this->user = new ArrayCollection();
    }

    /**
     * @ORM\PreUpdate
     * @ORM\PostUpdate
     */
    public function prePostUpdate() {
        $this->setUpdatedAt(new \DateTime());
        $this->setRuleIdentifier(md5($this->getRule()));
    }

    /**
     * Returns list of valid statuses
     * @return array
     */
    public static function getValidStatusList() {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_DEACTIVATED,
            self::STATUS_DELETED,
        ];
    }

    public function getArray() {
        return get_object_vars($this);
    }
}