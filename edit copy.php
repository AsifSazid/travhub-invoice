<?php
// server/invoice-update.php
session_start();
require_once 'db-connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    $conn->begin_transaction();
    
    // Get invoice data
    $invoice_id = intval($_POST['invoice_id']);
    $invoice_no = $conn->real_escape_string($_POST['invoice_no']);
    $date = $conn->real_escape_string($_POST['date']);
    $client_title = $conn->real_escape_string($_POST['client_title']);
    $client_phone_no = $conn->real_escape_string($_POST['client_phone_no']);
    $client_cc = $conn->real_escape_string($_POST['client_cc']);
    $total_amount = floatval($_POST['total_amount']);
    $paid_amount = floatval($_POST['paid_amount']);
    $due_amount = floatval($_POST['due_amount']);
    
    // Update invoice
    $sql = "UPDATE invoices SET 
            invoice_no = '$invoice_no',
            date = '$date',
            client_title = '$client_title',
            client_phone_no = '$client_phone_no',
            client_cc = '$client_cc',
            total_amount = $total_amount,
            paid_amount = $paid_amount,
            due_amount = $due_amount,
            updated_at = NOW()
            WHERE id = $invoice_id";
    
    if (!$conn->query($sql)) {
        throw new Exception("Failed to update invoice: " . $conn->error);
    }
    
    // Handle work items
    $work_titles = $_POST['work_title'] ?? [];
    $work_qtys = $_POST['work_qty'] ?? [];
    $work_rates = $_POST['work_rate'] ?? [];
    $work_particulars = $_POST['work_particular'] ?? [];
    $amounts = $_POST['amount'] ?? [];
    $work_ids = $_POST['work_id'] ?? [];
    
    // Delete work items marked for deletion
    $delete_work_ids = $_POST['delete_work_id'] ?? [];
    foreach ($delete_work_ids as $delete_id) {
        if ($delete_id && $delete_id !== 'new') {
            $delete_sql = "DELETE FROM work_items WHERE id = " . intval($delete_id);
            $conn->query($delete_sql);
        }
    }
    
    // Update or insert work items
    for ($i = 0; $i < count($work_titles); $i++) {
        $work_id = $work_ids[$i];
        $work_title = $conn->real_escape_string($work_titles[$i]);
        $work_qty = floatval($work_qtys[$i]);
        $work_rate = floatval($work_rates[$i]);
        $work_particular = $conn->real_escape_string($work_particulars[$i]);
        $amount = floatval($amounts[$i]);
        
        if ($work_id === 'new') {
            // Insert new work item
            $sql = "INSERT INTO work_items (invoice_id, work_title, work_qty, work_rate, work_particular, amount) 
                    VALUES ($invoice_id, '$work_title', $work_qty, $work_rate, '$work_particular', $amount)";
        } else {
            // Update existing work item
            $sql = "UPDATE work_items SET 
                    work_title = '$work_title',
                    work_qty = $work_qty,
                    work_rate = $work_rate,
                    work_particular = '$work_particular',
                    amount = $amount
                    WHERE id = " . intval($work_id);
        }
        
        if (!$conn->query($sql)) {
            throw new Exception("Failed to save work item: " . $conn->error);
        }
    }
    
    // Handle bank information
    $bank_data = json_decode($_POST['banks'] ?? '[]', true);
    $delete_bank_ids = $_POST['deleteBankIds'] ?? [];
    
    // Delete bank info marked for deletion
    foreach ($delete_bank_ids as $delete_id) {
        if ($delete_id && $delete_id !== 'new') {
            $delete_sql = "DELETE FROM bank_info WHERE id = " . intval($delete_id);
            $conn->query($delete_sql);
        }
    }
    
    // Update or insert bank info
    foreach ($bank_data as $bank) {
        $bank_id = $bank['id'] ?? 'new';
        $vendor_bank = $conn->real_escape_string($bank['vendor_bank'] ?? '');
        $vendor_bank_account = $conn->real_escape_string($bank['vendor_bank_account'] ?? '');
        $vendor_bank_branch = $conn->real_escape_string($bank['vendor_bank_branch'] ?? '');
        $vendor_bank_routing = $conn->real_escape_string($bank['vendor_bank_routing'] ?? '');
        
        if ($bank_id === 'new') {
            $sql = "INSERT INTO bank_info (invoice_id, vendor_bank, vendor_bank_account, vendor_bank_branch, vendor_bank_routing) 
                    VALUES ($invoice_id, '$vendor_bank', '$vendor_bank_account', '$vendor_bank_branch', '$vendor_bank_routing')";
        } else {
            $sql = "UPDATE bank_info SET 
                    vendor_bank = '$vendor_bank',
                    vendor_bank_account = '$vendor_bank_account',
                    vendor_bank_branch = '$vendor_bank_branch',
                    vendor_bank_routing = '$vendor_bank_routing'
                    WHERE id = " . intval($bank_id);
        }
        
        if (!$conn->query($sql)) {
            throw new Exception("Failed to save bank info: " . $conn->error);
        }
    }
    
    // Handle MFS information
    $mfs_data = json_decode($_POST['mfs'] ?? '[]', true);
    $delete_mfs_ids = $_POST['deleteMfsIds'] ?? [];
    
    // Delete MFS info marked for deletion
    foreach ($delete_mfs_ids as $delete_id) {
        if ($delete_id && $delete_id !== 'new') {
            $delete_sql = "DELETE FROM mfs_info WHERE id = " . intval($delete_id);
            $conn->query($delete_sql);
        }
    }
    
    // Update or insert MFS info
    foreach ($mfs_data as $mfs) {
        $mfs_id = $mfs['id'] ?? 'new';
        $vendor_mfs_title = $conn->real_escape_string($mfs['vendor_mfs_title'] ?? '');
        $vendor_mfs_type = $conn->real_escape_string($mfs['vendor_mfs_type'] ?? '');
        $vendor_amount_note = $conn->real_escape_string($mfs['vendor_amount_note'] ?? '');
        $vendor_mfs_account = json_encode($mfs['vendor_mfs_account'] ?? []);
        
        if ($mfs_id === 'new') {
            $sql = "INSERT INTO mfs_info (invoice_id, vendor_mfs_title, vendor_mfs_type, vendor_mfs_account, vendor_amount_note) 
                    VALUES ($invoice_id, '$vendor_mfs_title', '$vendor_mfs_type', '$vendor_mfs_account', '$vendor_amount_note')";
        } else {
            $sql = "UPDATE mfs_info SET 
                    vendor_mfs_title = '$vendor_mfs_title',
                    vendor_mfs_type = '$vendor_mfs_type',
                    vendor_mfs_account = '$vendor_mfs_account',
                    vendor_amount_note = '$vendor_amount_note'
                    WHERE id = " . intval($mfs_id);
        }
        
        if (!$conn->query($sql)) {
            throw new Exception("Failed to save MFS info: " . $conn->error);
        }
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Invoice updated successfully',
        'invoice_id' => $invoice_id
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>