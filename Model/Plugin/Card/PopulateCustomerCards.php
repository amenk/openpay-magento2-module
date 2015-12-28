<?php
/**
 * Created by Xavier de Garay.
 * User: degaray
 * Date: 3/12/15
 * Time: 12:36 PM
 */

namespace Degaray\Openpay\Model\Plugin\Card;

use Degaray\Openpay\Model\Adapter\OpenpayCreditCard;
use Degaray\Openpay\Model\ResourceModel\Card\CollectionFactory as CardCollectionFactory;
use Magento\Customer\Api\Data\CustomerExtensionFactory;
use Magento\Customer\Model\Customer;

/**
 * Class PopulateCustomerCards
 * @package Degaray\Openpay\Model\Plugin\Card
 */
class PopulateCustomerCards
{
    const CUSTOMER_EXTENSION_FACTORY_PATH = 'Magento\Customer\Api\Data\CustomerExtensionFactory';
    /**
     * @var \Magento\Customer\Api\Data\CustomerExtensionFactory
     */
    protected $customerExtensionFactory;

    /**
     * @var CardCollectionFactory
     */
    protected $cardCollectionFactory;

    /**
     * @var OpenpayCreditCard
     */
    protected $creditCard;

    /**
     * PopulateCustomerCards constructor.
     * @param CustomerExtensionFactory $customerExtensionFactory
     * @param CardCollectionFactory $cardCollectionFactory
     * @param OpenpayCreditCard $creditCard
     */
    public function __construct(
        CustomerExtensionFactory $customerExtensionFactory,
        CardCollectionFactory $cardCollectionFactory,
        OpenpayCreditCard $creditCard
    ) {
        $this->customerExtensionFactory = $customerExtensionFactory;
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->creditCard = $creditCard;
    }

    /**
     * @param Customer $customer
     * @param $customerDataObject
     * @return mixed
     */
    public function afterGetDataModel(Customer $customer, $customerDataObject)
    {
        $cardCollection = $this->cardCollectionFactory->create();
        $customerId = $customer->getId();
        $customerCards = $cardCollection->getCustomerCardsByCustomerId($customerId);
        $extension = $this->customerExtensionFactory->create()->setOpenpayCard($customerCards);
        $customerDataObject->setExtensionAttributes($extension);

        return $customerDataObject;
    }
}
