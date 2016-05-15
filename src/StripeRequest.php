<?php

namespace striperequest;



/**
 * Class StripeRequest
 *
 * @version 1.0.0
 * @uses curl
 *
 * @author Damjan Krstevski
 *
 * @license https://opensource.org/licenses/MIT MIT
 */
/**
 * Class StripeRequest
 * @package striperequest
 */
class StripeRequest
{
    /**
     * Stripe API URL
     *
     * @since 1.0.0
     * @access private
     *
     * @var string
     */
    private $apiUrl = 'https://api.stripe.com';

    /**
     * Stripe API version
     *
     * @since 1.0.0
     * @access private
     *
     * @var string
     */
    private $apiVersion;

    /**
     * Stripe secret key
     *
     * @since 1.0.0
     * @access private
     *
     * @var string
     */
    private $secretKey;



    /**
     * StripeRequest constructor
     *
     * @since 1.0.0
     * @access public
     *
     * @throws Exception
     *
     * @param $secretKey
     * @param string $apiVersion
     */
    public function __construct($secretKey, $apiVersion = 'v1')
    {
        // Check for curl module if enabled
        if (!function_exists('curl_init'))
            throw new Exception('To use ' . __CLASS__ . ' please enable cURL module.');

        // Check if secret key is provided
        if (empty($secretKey))
            throw new Exception('You must to provide secret key [https://dashboard.stripe.com/account/apikeys]');

        // Set the secret key and api version
        $this->secretKey = $secretKey;
        $this->apiVersion = $apiVersion;
    }



    /**
     * Function to get the API URL
     *
     * @since 1.0.0
     * @access private
     *
     * @see https://stripe.com/docs/api
     *
     * @param string $endpoint
     *
     * @return string Stripe API URL
     */
    private function getApiUrl($endpoint = '')
    {
        // Generate and return api url
        return $this->apiUrl . '/' . $this->apiVersion . '/' . $endpoint;
    }



    /**
     * Generate URL-encoded query string
     *
     * @since 1.0.0
     * @access private
     *
     * @param array $data
     *
     * @return mixed
     */
    private function query($data = [])
    {
        return http_build_query($data, '', '&amp;');
    }



    /**
     * Function to call Stripe API
     * Use: request->('plans', 'get') to retreive all plans
     *
     * @since 1.0.0
     * @access public
     *
     * @see https://stripe.com/docs/api
     *
     * @param string $call API endpoint
     * @param string $method HTTP method [Default is GET]
     * @param array $data URL encoded fields
     *
     * @return string Response from Stripe in JSON
     */
    public final function request($call, $method = 'GET', $data = [])
    {
        // Get the full call API url
        $url = $this->getApiUrl($call);

        // Initialize curl request
        $request = curl_init();

        // Configure request
        curl_setopt_array(
            $request,
            [
                CURLOPT_CUSTOMREQUEST   => strtoupper($method),
                CURLOPT_RETURNTRANSFER 	=> 1,
                CURLOPT_HEADER 			=> 0,
                CURLOPT_POSTFIELDS      => $this->query($data),
                CURLOPT_HTTPHEADER      => ['Authorization: Bearer ' . $this->secretKey],
                CURLOPT_URL 			=> filter_var($url, FILTER_VALIDATE_URL),
                CURLOPT_USERAGENT 		=> 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1',
            ]
        );

        // Execute request and get the response
        $output = curl_exec($request);
        curl_close($request);

        return $output;
    }



    /**
     * Retrieve a subscription
     * Retrieves the subscription with the given ID.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $subscriptionId ID of subscription to retrieve.
     *
     * @see https://stripe.com/docs/api#retrieve_subscription
     *
     * @return string Returns the subscription object.
     */
    public function getSubscription($subscriptionId)
    {
        $call = 'subscriptions/' . $subscriptionId;
        return $this->request($call);
    }



    /**
     * List active subscriptions
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $limit Number of objects. Limit can range between 1 and 100 items.
     * @param array $args Optional additional filter arguments
     *
     * @see https://stripe.com/docs/api#list_subscriptions
     *
     * @return string Returns a list of your active subscriptions.
     */
    public function listSubscriptions($limit = 10, $args = [])
    {
        $args['limit'] = $limit;
        $call = 'subscriptions?' . $this->query($args);
        return $this->request($call);
    }



    /**
     * Retrieves the details of an existing customer.
     * You need only supply the unique customer identifier that was returned upon customer creation.
     * NOTE: When requesting the ID of a customer that has been deleted,
     * a subset of the customer’s information will be returned,
     * including a deleted property, which will be true.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $customerId The identifier of the customer to be retrieved.
     *
     * @see https://stripe.com/docs/api#retrieve_customer
     *
     * @return string Returns a customer object if a valid identifier was provided.
     */
    public function getCustomer($customerId)
    {
        $call = 'customers/' . $customerId;
        return $this->request($call);
    }



    /**
     * List all customers
     * The customers are returned sorted by creation date,
     * with the most recent customers appearing first.
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $limit Number of objects. Limit can range between 1 and 100 items.
     * @param array $args Optional additional filter arguments
     *
     * @see https://stripe.com/docs/api#list_customers
     *
     * @return string Returns a list of your customers.
     */
    public function listCustomers($limit = 10, $args = [])
    {
        $args['limit'] = $limit;
        $call = 'customers?' . $this->query($args);
        return $this->request($call);
    }



    /**
     * Retrieve a card for a customer
     * Details about a specific card stored on the customer.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $customerId The ID of the customer.
     * @param string $cardId The ID of the card to be retrieved.
     *
     * @see https://stripe.com/docs/api#retrieve_card
     *
     * @return string Returns the card object.
     */
    public function getCard($customerId, $cardId)
    {
        $call = 'customers/' . $customerId . '/sources/' . $cardId;
        return $this->request($call);
    }



    /**
     * List all cards for the customer
     * You can see a list of the cards belonging to a customer or recipient.
     * Note that the 10 most recent sources are always available on the customer object.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $customerId The ID of the customer whose cards will be retrieved
     * @param int $limit Number of objects. Limit can range between 1 and 100 items.
     * @param array $args Optional additional filter arguments
     *
     * @see https://stripe.com/docs/api#list_cards
     *
     * @return string Returns a list of the cards stored on the customer, recipient, or account.
     */
    public function listCards($customerId, $limit = 10, $args = [])
    {
        $args['limit'] = $limit;
        $args['object'] = 'card';
        $call = 'customers/' . $customerId . '/sources?' . $this->query($args);
        return $this->request($call);
    }



    /**
     * Retrieve a charge
     * Retrieves the details of a charge that has previously been created.
     * The same information is returned when creating or refunding the charge.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $chargeId The identifier of the charge to be retrieved.
     *
     * @see https://stripe.com/docs/api#retrieve_charge
     * 
     * @return string Returns a charge if a valid identifier was provided, and returns an error otherwise.
     */
    public function getCharge($chargeId)
    {
        $call = 'charges/' . $chargeId;
        return $this->request($call);
    }



    /**
     * List all charges
     * Returns a list of charges you’ve previously created.
     * The charges are returned in sorted order, with the most recent charges appearing first.
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $limit Number of objects. Limit can range between 1 and 100 items.
     * @param array $args Optional additional filter arguments
     *
     * @see https://stripe.com/docs/api#list_charges
     *
     * @return string
     */
    public function listCharges($limit = 10, $args = [])
    {
        $args['limit'] = $limit;
        $call = 'charges?' . $this->query($args);
        return $this->request($call);
    }

} // End of class StripeRequest