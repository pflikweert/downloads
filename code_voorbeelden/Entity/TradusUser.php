<?php

namespace TradusBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation\Exclude;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\TradusUserRepository")
 * @ORM\Table(name="tradus_users",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="email_idx", columns={"email"})},
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class TradusUser  {

    /* User has an active account with password */
    const STATUS_ACTIVE     = 100;
    /* User is been soft deleted */
    const STATUS_DELETED    = -200;
    /* User has requested a password/account but not confirmed yet */
    const STATUS_PENDING    = 10;
    /* User data has been recorded but has not requested an account */
    const STATUS_NO_ACCOUNT = 20;

    const AVAILABLE_FIELDS = [
        'email',
        'password',
        'id',
        'first_name',
        'last_name',
        'phone',
        'company',
        'country',
        'status',
        'ip',
        'send_alerts',
        'google_id',
        'facebook_id',
        'preferred_locale',
    ];

    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="id", type="integer", unique=true)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @Assert\Email(message = "The email '{{ value }}' is not a valid email.")
     * @Assert\NotBlank(message = "email can not be empty")
     * @ORM\Column(name="email", type="string", length=255, unique=true, nullable=false)
     */
    protected $email;

    /**
     * @var string
     * @Assert\Type("string")
     * @Assert\NotBlank(message = "password can not be empty")
     * @ORM\Column(name="password", type="string", length=255, nullable=false)
     */
    protected $password;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="first_name", type="string", length=255, nullable=true)
     */
    protected $first_name;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="last_name", type="string", length=255, nullable=true)
     */
    protected $last_name;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="preferred_locale", type="string", length=10, nullable=true)
     */
    protected $preferred_locale;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="phone", type="string", length=255, nullable=true)
     */
    protected $phone;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="country", type="string", length=255, nullable=true)
     */
    protected $country;

    /**
     * @var string
     * @ORM\Column(name="company", type="string", length=255, nullable=true)
     */
    protected $company;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="confirmation_token", type="string", length=255, nullable=true)
     */
    protected $confirmation_token;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     * @Assert\Type("integer")
     * @Assert\Choice(callback={"TradusBundle\Entity\TradusUser", "getValidStatusList"}, strict=true)
     * @Exclude
     */
    protected $status = self::STATUS_NO_ACCOUNT;

    /**
     * @var integer
     *
     * @ORM\Column(name="invalid_email", type="integer", nullable=true)
     * @Assert\Type("integer")
     * @Exclude
     */
    protected $invalid_email = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=255, nullable=true)
     */
    protected $ip;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="agreement_date", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    protected $agreement_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    protected $last_login;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="accepted_alerts_date", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    protected $accepted_alerts_date;

    /**
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\SimilarOfferAlert", mappedBy="user", fetch="EXTRA_LAZY")
     * @Exclude
     */
    private $similar_offer_alerts;

    /**
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\SearchAlert", mappedBy="user", fetch="EXTRA_LAZY")
     */
    private $search_alerts;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\DateTime()
     */
    protected $created_at;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     * @Assert\DateTime()
     */
    protected $updated_at;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook_id", type="string", nullable=true)
     * @Assert\Type("string")
     */
    protected $facebookID;

    /**
     * @var string
     *
     * @ORM\Column(name="google_id", type="string", nullable=true)
     * @Assert\Type("string")
     */
    protected $googleID;

    /**
     * @ORM\PreUpdate
     * @ORM\PostUpdate
     */
    public function prePostUpdate() {
        $this->setUpdatedAt(new \DateTime());
    }

    public function setFacebookId($facebook_id) {
        $this->facebookID = $facebook_id;
    }

    public function getFaceBookId() {
        return $this->facebookID;
    }

    public function setGoogleId($google_id) {
        $this->googleID = $google_id;
    }

    public function getGoogleId() {
        return $this->googleID;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void {
        $this->email = strtolower(trim($email));
    }

    /**
     * @return string
     */
    public function getFirstName() {
        return $this->first_name;
    }

    /**
     * @param string $first_name
     */
    public function setFirstName(string $first_name): void {
        $this->first_name = trim($first_name);
    }

    /**
     * @return string
     */
    public function getLastName() {
        return $this->last_name;
    }

    /**
     * @param string $last_name
     */
    public function setLastName(string $last_name): void {
        $this->last_name = trim($last_name);
    }

    /**
     * @return string
     */
    public function getPhone() {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone(string $phone): void {
        $this->phone = trim($phone);
    }

    /**
     * @return string
     */
    public function getCountry() {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry(string $country): void {
        $this->country = trim($country);
    }

    /**
     * @return string
     */
    public function getCompany() {
        return $this->company;
    }

    /**
     * @param string $company
     */
    public function setCompany(string $company): void {
        $this->company = trim($company);
    }

    /**
     * @return int
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void {
        $this->status = $status;
    }

    /**
     * 0 = valid email
     * 1 = invalid email
     * @param int $invalid_email
     */
    public function setInValidEmail(int $invalid_email) {
        $this->invalid_email = $invalid_email;
    }

    /**
     * @return int
     */
    public function getInValidEmail() {
        return $this->invalid_email;
    }

    /**
     * @return string
     */
    public function getIp() {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp(string $ip): void {
        $this->ip = trim($ip);
    }

    /**
     * @return string
     */
    public function getConfirmationToken() {
        return $this->confirmation_token;
    }

    /**
     * @param string $confirmation_token
     */
    public function setConfirmationToken($confirmation_token): void {
        $this->confirmation_token = $confirmation_token;
    }

    /**
     * When did the user accepted the agreement, when this is set he accepted
     * @param \DateTime $agreement_date
     */
    public function setAgreementDate(\DateTime $agreement_date) {
        $this->agreement_date = $agreement_date;
    }

    /**
     * @return \DateTime
     */
    public function getAgreementDate() {
        return $this->agreement_date;
    }

    /**
     * @param \DateTime $last_login
     */
    public function setLastLogin(\DateTime $last_login) {
        $this->last_login = $last_login;
    }

    /**
     * @return \DateTime
     */
    public function getLastLogin() {
        return $this->last_login;
    }

    /**
     * @param \DateTime $accepted_alerts_date
     */
    public function setAcceptedAlertsDate(\DateTime $accepted_alerts_date) {
        $this->accepted_alerts_date = $accepted_alerts_date;
    }

    /**
     * @return \DateTime
     */
    public function getAcceptedAlertsDate() {
        return $this->accepted_alerts_date;
    }

    /**
     * Does this user opt-in for alert emails?
     * @return bool
     */
    public function canSendAlertEmails() {
        if(null !== $this->accepted_alerts_date && $this->accepted_alerts_date instanceof \DateTime) {
            return true;
        }
        return false;
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
     * @return string
     */
    public function getPreferredLocale() {
        return $this->preferred_locale;
    }

    /**
     * @param $preferred_locale
     */
    public function setPreferredLocale($preferred_locale) {
        $this->preferred_locale = $preferred_locale;
    }

    /**
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * @return SimilarOfferAlert
     */
    public function getSimilarOfferAlerts() {
        return $this->similar_offer_alerts;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void {
        $this->password = $this->passwordHash($password);
    }

    /**
     * @return array of valid statuses
     */
    public static function getValidStatusList() {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_PENDING,
            self::STATUS_NO_ACCOUNT,
            self::STATUS_DELETED,
        ];
    }

    /**
     * Is this user active and not deleted or pending?
     * @return bool
     */
    public function isActiveUser() {
        if ($this->getStatus() == TradusUser::STATUS_ACTIVE || $this->getStatus() == TradusUser::STATUS_NO_ACCOUNT) {
            return true;
        }
        return false;
    }

    public function isDeleted() {
        if ($this->getStatus() == TradusUser::STATUS_DELETED) {
            return true;
        }
        return false;
    }

    /**
     * Disables the alert emails to set the date to null
     */
    public function disableAlertEmails() {
        $this->accepted_alerts_date = null;
    }

    /**
     * @return string
     */
    public function generateToken() {
        $tokenData = $this->getId().$this->getEmail().$this->getPassword();
        return md5($tokenData);
    }

    /**
     * @param array $data
     * @return string
     */
    static public function generateTimeBasedCode(array $data) {
        $hashKey = "fdslkPN8er0wD-q9e+rij!dewhgjqe";
        $encryptionKey = "PJ*sdfrqw%432.kjYY";

        $data = array_merge(array('timestamp' => time()), $data);
        // convert the array to json
        $jsonData = json_encode($data);
        // encrypt the data
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher="AES-128-CBC"));
        $encryptedData = openssl_encrypt($jsonData, $cipher, $encryptionKey, 0, $iv);
        // create a hash key for the data
        $hash = hash_hmac('sha256', $encryptedData, $hashKey, true);
        // return the encrypted data and hash
        $ciphertext = rtrim(strtr(base64_encode(( $iv.$hash.$encryptedData )), '+/', '-_'), '=');

        return $ciphertext;
    }

    /**
     * @param string $ciphertext
     * @param int $lifetimeHours
     * @return bool|mixed|string
     * @throws \Exception
     */
    static public function validateTimeBasedCode(string $ciphertext, $lifetimeHours = 168) {
        $hashKey = "fdslkPN8er0wD-q9e+rij!dewhgjqe";
        $encryptionKey = "PJ*sdfrqw%432.kjYY";

        $c = base64_decode(strtr(($ciphertext), '-_', '+/'));
        $ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
        $iv = substr($c, 0, $ivlen);
        $hash = substr($c, $ivlen, $sha2len=32);
        $data = substr($c, $ivlen+$sha2len);
        $original_plaintext = openssl_decrypt($data, $cipher, $encryptionKey, 0, $iv);
        $calcmac = hash_hmac('sha256', $data, $hashKey, true);
        if (!hash_equals($hash, $calcmac)) {
            //PHP 5.6+ timing attack safe comparison
            throw new \Exception('Hash check failed!', 73100);
        }
        $data = json_decode($original_plaintext, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \Exception('Invalid data decrypted / JSON decode failed!', 73101);
        }
        // verify that an array has been received
        if (!is_array($data)) {
            throw new \Exception('Decrypted data is not an array!', 73102);
        }
        // check for timeout / prevent replay attacks
        if ($lifetimeHours !== 0) {
            $tokenAgeInSeconds =  intval(time()) - intval($data['timestamp']);
            if (!isset($data['timestamp'])) {
                throw new \Exception('Timeout check failed, no timestamp available!', 73103);
            } elseif ($tokenAgeInSeconds > ($lifetimeHours * 3600)) {
                throw new \Exception('Timeout, possibly replay attack!', 73104);
            }
        }
        // return the data
        return $data;
    }

    /**
     * @param string $password
     * @return bool|string
     */
    public function passwordHash(string $password) {
        $options['cost'] = 10;
        return password_hash($password, PASSWORD_BCRYPT, $options);
    }

    /**
     * @param string $password
     * @return bool
     */
    public function passwordValidate(string $password) {
        return password_verify($password, $this->getPassword());
    }

    /**
     * Get information about the password hash. Returns an array of the information
     * that was used to generate the password hash.
     *
     * @return array
     */
    public function passwordInformation() {
        $passwordInformation = password_get_info($this->getPassword());
        return $passwordInformation;
    }

    /**
     * @param int $length
     * @return bool|string
     */
    public function generatePassword(int $length = 8) {
        $chars   = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ23456789";
        $numbers = "23456789";
        $special = "!@#$%&*_-=+";

        $password = substr( str_shuffle( $chars ), 0, $length-2 );
        $password .= substr( str_shuffle( $numbers ), 0, 1);
        $password .= substr( str_shuffle( $special ), 0, 1);

        return $password;
    }

    /**
     * Transforms object data into array
     *
     * @param TradusUser $user
     * @return array
     */
    public static function transform(TradusUser $user) {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'phone' => $user->getPhone(),
            'country' => $user->getCountry(),
            'company' => $user->getCompany(),
            'status' => $user->getStatus(),
            'ip' => $user->getIp(),
            'agreement_date' => $user->getAgreementDate(),
            'accepted_alerts_date' => $user->getAcceptedAlertsDate(),
            'created_at' => $user->getCreatedAt(),
            'updated_at' => $user->getUpdatedAt(),
            'google_id' => $user->getGoogleId(),
            'facebook_id' => $user->getFaceBookId(),
            'confirmation_token' => $user->getConfirmationToken(),
            'last_login' => $user->getLastLogin(),
            'preferred_locale' => $user->getPreferredLocale(),
            ];
    }

    /**
     * Helper function to set variables by array
     *
     * @param array $params
     */
    public function setValues(array $params) {

        if (isset($params['id']))
            $this->setId($params['id']);
        if (isset($params['email']))
            $this->setEmail($params['email']);
        if (isset($params['password']))
            $this->password = $params['password'];

        if (isset($params['agreement_date'])) {
            $date = $params['agreement_date'];
            if (is_array($date) && isset($date['date']))
                $date = $date['date'];
            $this->setAgreementDate(new \DateTime($date));
        }

        if (isset($params['accepted_alerts_date'])) {
            $date = $params['accepted_alerts_date'];
            if (is_array($date) && isset($date['date']))
                $date = $date['date'];
            $this->setAcceptedAlertsDate(new \DateTime($date));
        }

        if (isset($params['created_at'])) {
            $date = $params['created_at'];
            if (is_array($date) && isset($date['date']))
                $date = $date['date'];
            $this->setCreatedAt(new \DateTime($date));
        }

        if (isset($params['updated_at'])) {
            $date = $params['updated_at'];
            if (is_array($date) && isset($date['date']))
                $date = $date['date'];
            $this->setUpdatedAt(new \DateTime($date));
        }
        if (isset($params['first_name']))
            $this->setFirstName($params['first_name']);
        if (isset($params['last_name']))
            $this->setLastName($params['last_name']);
        if (isset($params['country']))
            $this->setCountry($params['country']);
        if (isset($params['company']))
            $this->setCompany($params['company']);
        if (isset($params['phone']))
            $this->setPhone($params['phone']);
        if (isset($params['ip']))
            $this->setIp($params['ip']);
        if (isset($params['status']))
            $this->setStatus($params['status']);
        if (isset($params['facebook_id']))
            $this->setFacebookId($params['facebook_id']);
        if (isset($params['google_id']))
            $this->setGoogleId($params['google_id']);
        if (isset($params['last_login'])) {
            $this->setLastLogin(new \DateTime());
        }
        if (isset($params['preferred_locale'])) {
            $this->setPreferredLocale($params['preferred_locale']);
        }


        if (isset($params['send_alerts'])) {
            if ($params['send_alerts'] == true) {
                $acceptDate = new \DateTime();
                $this->setAcceptedAlertsDate($acceptDate);
            } else {
                $this->disableAlertEmails();
            }
        }
    }
}
