<?php
/*
 * This file is part of the Dhru Fusion Pro Payment Gateway.
 *
 * @license    Proprietary
 * @copyright  2024 Dhru.com
 * @author     Dhru Fusion Team
 * @description Custom Payment Gateway Development Kit for Dhru Fusion Pro.
 * @powered    Powered by Dhru.com
 *
 * Entry point for processing an order creation and payment flow:
 * 1. Validate the required authentication token and input parameters.
 * 2. Create a new order in the local system's database with the input details.
 * 3. Retrieve the local order ID for the newly created order.
 * 4. Generate the hosted checkout URL for redirection, combining the server URL and the local order ID.
 * 5. Prepare the response including the order ID and the hosted checkout URL.
 * 6. Return a success response if the order creation and checkout URL generation are successful.
 * 7. Handle errors gracefully and return an error response in case of failures.
 *
+---------------------------------------------+
|              Dhru Fusion System             |
|        Initiates `create_order` Request     |
+---------------------------------------------+
                      |
                      v
+---------------------------------------------+
|         `create_order` Invokes PayPal       |
|        to Create a New Checkout Order       |
+---------------------------------------------+
                      |
                      v
+---------------------------------------------+
|         PayPal Responds with a Checkout     |
|               Approval URL                  |
+---------------------------------------------+
                      |
                      v
+---------------------------------------------+
|       `create_order` Returns the Checkout   |
|           URL to Dhru Fusion System         |
+---------------------------------------------+
                      |
                      v
+---------------------------------------------+
|         Dhru Fusion Receives and Stores     |
|           the PayPal Checkout URL           |
+---------------------------------------------+
 */

require_once __DIR__ . '/../core/OrderModel.php';
validateApiKey();

global $input;


$requiredFields = ['amount','currency_code','description','customer_name','customer_email','custom_id','ipn_url','success_url','fail_url'];

$validationResult = validateInputs($input, $requiredFields);
if ($validationResult !== true) {
    output('error', $validationResult, null, 400);
}

$orderModel = new OrderModel();

// Create the order
$orderData = [
    'amount' => $input['amount'],
    'currency_code' => $input['currency_code'],
    'description' => $input['description'],
    'customer_name' => $input['customer_name'],
    'customer_email' => $input['customer_email'],
    'custom_id' => $input['custom_id'],
    'ipn_url' => $input['ipn_url'],
    'success_url' => $input['success_url'],
    'fail_url' => $input['fail_url'],
    'order_date' => date('Y-m-d H:i:s'),
];
$orderId = $orderModel->createOrder($orderData);


if ($orderId) {

    /*
     * Call the 3rd-party payment processing service to retrieve the checkout URL.
     *
     * Steps:
     * 1. Use the external payment gateway SDK or API to initiate the payment process.
     * 2. Pass the relevant input parameters required by the payment gateway, such as:
     *    - Order amount
     *    - Currency code
     *    - Order description
     *    - Notification URL (IPN URL)
     *    - Success URL
     *    - Failure URL
     * 3. Handle potential errors during the API request and provide appropriate error responses.
     *
     * Input parameters to include:
     * - Success URL ($successUrl): URL to redirect to upon successful payment.
     * - Failure URL ($failUrl): URL to redirect to if payment fails.
     * - Notification URL ($inpUrl): URL to notify once payment is completed.
     *
     * Replace this placeholder code with actual payment gateway implementation when integrating.
     */

    // Get the current script path where index.php is located.
    $server_url = $_SERVER['SCRIPT_URI'];

    // The checksum is generated using the order ID, IPN URL, and order creation date-time which is not exposed to the public.
    $checksum = md5($orderId . $orderData['ipn_url'] . $orderData['order_date']);

    // Notification URL generated for the 3rd-party payment provider
    // This URL includes the order ID and a checksum for security.
    $inpUrl = "{$server_url}?action=ipn&checksum={$checksum}&order_id={$orderId}";


    // 3rd-party payment provider redirect to these URLs for payment status.
    //
    // Success URL: Redirects the user upon successful payment.
    // Failure URL: Redirects the user if the payment fails.
    //
    // Each URL includes the order ID and a checksum for security purposes
    // to ensure the integrity of the transaction data.
    $successUrl = "{$server_url}?action=ipn&checksum={$checksum}&order_id={$orderId}&success=true";
    $failUrl = "{$server_url}?action=ipn&checksum={$checksum}&order_id={$orderId}&fail=true";


    /*
     * This is a simple `checkout.html` file used as a sandbox to process success
     * and failure payments on a single page. Once connected to a live payment provider,
     * this file should be removed, and the live payment integration should be used.
     */
    $out = [];
    $out['order_id'] = $orderId;
    $out['order_url'] = "{$server_url}checkout.html?order_id={$orderId}&checksum={$checksum}";


    output('success', 'Order created successfully!', $out, 200);

} else {
    output('error', 'Failed to create order.', null, 500);
}
