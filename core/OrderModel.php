<?php
/*
 * This file is part of the Dhru Fusion Pro Payment Gateway.
 *
 * @license    Proprietary
 * @copyright  2024 Dhru.com
 * @author     Dhru Fusion Team
 * @description Custom Payment Gateway Development Kit for Dhru Fusion Pro.
 * @powered    Powered by Dhru.com
 */
require_once __DIR__ . '/../config/database.php';

class OrderModel
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function createOrder($orderData)
    {
        $query = "INSERT INTO orders (
        amount, 
        currency_code, 
        description, 
        customer_name, 
        customer_email, 
        custom_id, 
        ipn_url, 
        success_url, 
        fail_url, 
        order_date
    ) VALUES (
        :amount, 
        :currency_code, 
        :description, 
        :customer_name, 
        :customer_email, 
        :custom_id, 
        :ipn_url, 
        :success_url, 
        :fail_url, 
        :order_date
    )";

        $stmt = $this->conn->prepare($query);

        // Bind sanitized inputs
        $stmt->bindParam(':amount', $orderData['amount'], PDO::PARAM_STR); // Use a float type if needed
        $stmt->bindParam(':currency_code', $orderData['currency_code'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $orderData['description'], PDO::PARAM_STR);
        $stmt->bindParam(':customer_name', $orderData['customer_name'], PDO::PARAM_STR);
        $stmt->bindParam(':customer_email', $orderData['customer_email'], PDO::PARAM_STR);
        $stmt->bindParam(':custom_id', $orderData['custom_id'], PDO::PARAM_STR);
        $stmt->bindParam(':ipn_url', $orderData['ipn_url'], PDO::PARAM_STR);
        $stmt->bindParam(':success_url', $orderData['success_url'], PDO::PARAM_STR);
        $stmt->bindParam(':fail_url', $orderData['fail_url'], PDO::PARAM_STR);
        $stmt->bindParam(':order_date', $orderData['order_date'], PDO::PARAM_STR);

        try {
            if ($stmt->execute()) {
                return $this->conn->lastInsertId(); // Return new order ID
            }
        } catch (PDOException $e) {
            // Log the error for debugging
            output('error', 'Database Error: ' . $e->getMessage(), null, 500);
        }

        return false;
    }


    public function getOrderById($orderId)
    {
        $query = "SELECT * FROM orders WHERE order_id = :order_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC); // Return single order details
    }


    public function updateOrder($orderId, $orderData)
    {
        // Create the base query with placeholders
        $query = "UPDATE orders SET 
        status = :status, 
        received_amount = :received_amount, 
        transaction_id = :transaction_id
        WHERE order_id = :order_id";

        // Prepare the statement
        $stmt = $this->conn->prepare($query);

        // Bind sanitized inputs
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindParam(':status', $orderData['status'], PDO::PARAM_STR); // Use a float type if needed
        $stmt->bindParam(':received_amount', $orderData['received_amount'], PDO::PARAM_STR);
        $stmt->bindParam(':transaction_id', $orderData['transaction_id'], PDO::PARAM_STR);

        try {
            // Execute the query and return a success flag
            return $stmt->execute();
        } catch (PDOException $e) {
            // Log the error for debugging
            output('error', 'Database Error: ' . $e->getMessage(), null, 500);
        }

        return false;
    }

}
