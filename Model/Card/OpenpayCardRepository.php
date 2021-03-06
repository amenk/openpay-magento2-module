<?php
/**
 * Created by Xavier de Garay.
 * User: degaray
 * Date: 30/12/15
 * Time: 02:00 PM
 */

namespace Degaray\Openpay\Model\Card;

use Degaray\Openpay\Api\CardRepositoryInterface;
use Degaray\Openpay\Api\Data\CardInterface;
use Degaray\Openpay\Model\Mapper\CardMapper;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Openpay\Client\Adapter\OpenpayCardAdapterInterface;
use Openpay\Client\Adapter\OpenpayCustomerAdapterInterface;
use Openpay\Client\Exception\OpenpayException;

class OpenpayCardRepository implements CardRepositoryInterface
{
    const CUSTOMER_CARDS_CACHE_PREFIX = 'Customer_Cards_';

    const CACHE_TIME_SECONDS = 60;

    /**
     * @var OpenpayCustomerAdapterInterface
     */
    protected $customerAdapter;

    /**
     * @var OpenpayCardAdapterInterface
     */
    protected $cardAdapter;

    /**
     * @var CardMapper
     */
    protected $cardMapper;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * OpenpayCardRepository constructor.
     * @param OpenpayCustomerAdapterInterface $openpayCustomerAdapter
     * @param OpenpayCardAdapterInterface $openpayCardAdapter
     * @param CardMapper $cardMapper
     * @param CacheInterface $cache
     */
    public function __construct(
        OpenpayCustomerAdapterInterface $openpayCustomerAdapter,
        OpenpayCardAdapterInterface $openpayCardAdapter,
        CardMapper $cardMapper,
        CacheInterface $cache
    ) {
        $this->cardAdapter = $openpayCardAdapter;
        $this->customerAdapter = $openpayCustomerAdapter;
        $this->cardMapper = $cardMapper;
        $this->cache = $cache;
    }

    /**
     * @param string $customerId
     * @param CardInterface $card
     * @return \Openpay\Client\Type\OpenpayCardType
     * @throws LocalizedException
     */
    public function save($customerId, CardInterface $card)
    {
        $params = [
            'token_id' => $card->getToken(),
            'device_session_id' => $card->getDeviceSessionId(),
        ];

        try {
            $openpayCard = $this->cardAdapter->store($customerId, $params);
        } catch (OpenpayException $e) {
            throw new LocalizedException(__($e->getDescription()), $e);
        }

        $cacheIdentifier = $this->getCacheIdentifier($customerId);
        $this->cache->remove($cacheIdentifier);

        return $openpayCard;
    }

    /**
     * @param string $openpayCustomerId
     * @return CardInterface[]
     * @throws LocalizedException
     */
    public function getCardsByOpenpayCustomerId($openpayCustomerId)
    {
        $cacheIdentifier = $this->getCacheIdentifier($openpayCustomerId);

        $cardsArray = unserialize($this->cache->load($cacheIdentifier));

        if ($cardsArray === false) {
            try {
                $cardsArray = $this->cardAdapter->getList($openpayCustomerId);
            } catch (OpenpayException $e) {
                $description = $e->getDescription();
                if (is_null($description)) {
                    $description = 'We could not retrieve your payment gateway information.';
                }
                throw new LocalizedException(__($description), $e);
            }

            $this->cache->save(serialize($cardsArray), $cacheIdentifier, [], self::CACHE_TIME_SECONDS);
        }

        $openpayCards = [];
        foreach ($cardsArray as $card) {
            $openpayCards[] = $this->cardMapper->create($card);
        }

        return $openpayCards;
    }

    public function getById($cardId)
    {
        // TODO: Implement getById() method.
    }

    /**
     * @param CardInterface $cardInterface
     * @return bool
     * @throws LocalizedException
     */
    public function delete(CardInterface $cardInterface)
    {
        $cardId = $cardInterface->getCardId();
        $customerId = $cardInterface->getCustomerId();

        try {
            $deleted = $this->cardAdapter->delete($customerId, $cardId);
        } catch (OpenpayException $e) {
            throw new LocalizedException(__($e->getDescription()), $e);
        }

        $cacheIdentifier = $this->getCacheIdentifier($customerId);
        $this->cache->remove($cacheIdentifier);

        return $deleted;
    }

    public function deleteById($cardId)
    {
    }

    /**
     * @param $openpayCustomerId
     * @return string
     */
    protected function getCacheIdentifier($openpayCustomerId)
    {
        $cacheIdentifier = md5(self::CUSTOMER_CARDS_CACHE_PREFIX . $openpayCustomerId);
        return $cacheIdentifier;
    }

}