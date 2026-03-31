<?php
session_start();
include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bill_id = (int)$_POST['bill_id'];
    $patient_id = (int)$_POST['patient_id'];
    $payment_method = $_POST['payment_method'];
    
    // Validate inputs
    if (empty($bill_id) || empty($patient_id) || empty($payment_method)) {
        $_SESSION['payment_error'] = "All fields are required";
        header("Location: patient_profile.php");
        exit;
    }
    
    // Validate based on payment method
    switch ($payment_method) {
        case 'credit_card':
            $card_number = $_POST['card_number'];
            $expiry = $_POST['expiry'];
            $cvv = $_POST['cvv'];
            
            if (empty($card_number) || empty($expiry) || empty($cvv)) {
                $_SESSION['payment_error'] = "All card fields are required";
                header("Location: patient_profile.php");
                exit;
            }
            
            // Validate card number (basic validation)
            if (!preg_match('/^\d{4}\s?\d{4,6}\s?\d{4}$/', $card_number)) {
                $_SESSION['payment_error'] = "Invalid card number format";
                header("Location: patient_profile.php");
                exit;
            }
            
            // Validate expiry date
            if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
                $_SESSION['payment_error'] = "Invalid expiry date format (MM/YY)";
                header("Location: patient_profile.php");
                exit;
            }
            
            // Validate CVV
            if (!preg_match('/^\d{3,4}$/', $cvv)) {
                $_SESSION['payment_error'] = "Invalid CVV format";
                header("Location: patient_profile.php");
                exit;
            }
            break;
            
        case 'upi':
            $upi_id = $_POST['upi_id'];
            
            if (empty($upi_id)) {
                $_SESSION['payment_error'] = "UPI ID is required";
                header("Location: patient_profile.php");
                exit;
            }
            
            // Basic UPI validation
            if (!preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+$/', $upi_id)) {
                $_SESSION['payment_error'] = "Invalid UPI ID format";
                header("Location: patient_profile.php");
                exit;
            }
            break;
            
        case 'net_banking':
            $bank_name = $_POST['bank_name'];
            $account_number = $_POST['account_number'];
            
            if (empty($bank_name) || empty($account_number)) {
                $_SESSION['payment_error'] = "Bank name and account number are required";
                header("Location: patient_profile.php");
                exit;
            }
            break;
            
        case 'wallet':
            $wallet_number = $_POST['wallet_number'];
            
            if (empty($wallet_number)) {
                $_SESSION['payment_error'] = "Wallet number is required";
                header("Location: patient_profile.php");
                exit;
            }
            break;
            
        default:
            $_SESSION['payment_error'] = "Invalid payment method selected";
            header("Location: patient_profile.php");
            exit;
    }
    
    // Update bill status to Paid (store payment method only since payment_date column doesn't exist)
    $update_query = "UPDATE medical_bills SET payment_method = '$payment_method' WHERE bill_id = $bill_id AND patient_id = $patient_id";
    
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['payment_success'] = "Payment processed successfully via " . ucfirst(str_replace('_', ' ', $payment_method)) . "!";
        header("Location: patient_profile.php");
        exit;
    } else {
        $_SESSION['payment_error'] = "Payment processing failed. Please try again.";
        header("Location: patient_profile.php");
        exit;
    }
} else {
    header("Location: patient_profile.php");
    exit;
}
?>
