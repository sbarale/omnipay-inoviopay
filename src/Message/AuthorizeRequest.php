<?php

namespace Omnipay\InovioPay\Message;

/**
 * Class AuthorizeRequest
 *
 * This is the parent class of Card payment and Token payment (Purchase Request)
 *
 * @date      3/5/18
 * @author    markbonnievestil
 * @copyright Copyright (c) Infostream Group
 */
class AuthorizeRequest extends AbstractRequest
{
    /**
     * @return mixed
     */
    public function getP3dsTransId()
    {
        return $this->getParameter('p3dsTransId');
    }

    /**
     * @param $value
     * @return \Omnipay\Common\Message\AbstractRequest
     */
    public function setP3dsTransId($value)
    {
        return $this->setParameter('p3dsTransId', $value);
    }

    /**
     * @return mixed
     */
    public function getPares()
    {
        return $this->getParameter('pares');
    }

    /**
     * @param $value
     * @return \Omnipay\Common\Message\AbstractRequest
     */
    public function setPares($value)
    {
        return $this->setParameter('pares', $value);
    }

    /**
     * @return mixed
     */
    public function getIsAutoRebill()
    {
        return $this->getParameter('isAutoRebill');
    }

    /**
     * @param $value
     * @return \Omnipay\Common\Message\AbstractRequest
     */
    public function setIsAutoRebill($value)
    {
        return $this->setParameter('isAutoRebill', $value);
    }

    /**
     * @return array
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function getData()
    {
        $data = parent::getData();

        $this->validate('amount', 'currency', 'productId');

        $data['request_action']   = 'CCAUTHORIZE';
        $data['li_prod_id_1']     = $this->getProductId();
        $data['li_value_1']       = $this->getAmount();
        $data['request_currency'] = strtolower($this->getCurrency());
        $data['xtl_ip']           = $this->getClientIp();

        // identifier generated by the merchant website.
        if ($this->getTransactionId()) {
            $data['xtl_order_id'] = $this->getTransactionId();
        }

        if ($this->getTransactorId()) {
            $data['xtl_cust_id']  = $this->getTransactorId();
        }

        if ($this->getCardReference()) {
            $this->validate('customerReference');

            $data['pmt_id']  = $this->getCardReference();
            $data['cust_id'] = $this->getCustomerReference();

            if ($this->getIsAutoRebill()) {
                $data['request_rebill'] = 1;

                // as per Inovio, we don't have to send IP for auto-rebills
                unset($data['xtl_ip']);
            }
        } else {
            $this->validate('card');
            $card = $this->getCard();

            $data['cust_fname']        = $card->getFirstName();
            $data['cust_lname']        = $card->getLastName();
            $data['cust_email']        = $card->getEmail();
            $data['bill_addr']         = $card->getBillingAddress1() . ' ' . $card->getBillingAddress2();
            $data['bill_addr_city']    = $card->getBillingCity();
            $data['bill_addr_zip']     = $card->getBillingPostcode();
            $data['bill_addr_state']   = $card->getBillingState();
            $data['bill_addr_country'] = $card->getBillingCountry();
            $data['pmt_numb']          = $card->getNumber();
            $data['pmt_key']           = $card->getCvv();
            $data['pmt_expiry']        = $card->getExpiryDate('mY');

            // if 3DS the fields are empty, meaning this is just to check if card is 3DS enrolled
            // if not empty, we should not send this parameter. Refer to CompletePurchaseRequest class
            if (!$this->getP3dsTransId() && !$this->getPares()) {
                // to check if card is 3DS enrolled
                $data['request_enrollment'] = 1;
            }
        }

        return $data;
    }

    // TODO : Support for multiple product items
}