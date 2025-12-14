<?php
// server/get-next-serial.php
header('Content-Type: application/json');

// Database connection - using PDO
require __DIR__ . '/db_connection.php';

function getMonthAbbr($month) {
    $months = [
        '01' => 'JAN', '02' => 'FEB', '03' => 'MAR', '04' => 'APR',
        '05' => 'MAY', '06' => 'JUN', '07' => 'JUL', '08' => 'AUG',
        '09' => 'SEP', '10' => 'OCT', '11' => 'NOV', '12' => 'DEC'
    ];
    
    return $months[$month] ?? 'ERR';
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $year = $_GET['year'] ?? date('y');
    $month = $_GET['month'] ?? date('m');
    
    // Validate inputs
    if (!is_numeric($year) || !is_numeric($month)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid year or month',
            'nextSerial' => 1
        ]);
        exit;
    }
    
    try {
        // Get the highest serial for this year and month
        $monthAbbr = getMonthAbbr(str_pad($month, 2, '0', STR_PAD_LEFT));
        $prefix = "TIF-{$monthAbbr}-{$year}-";
        $likePattern = $prefix . "%";
        
        // Debug: Log the pattern
        error_log("Looking for pattern: {$likePattern}");
        
        // Method 1: Get the highest invoice number for this pattern
        $sql = "SELECT invoice_no FROM invoices 
                WHERE invoice_no LIKE :pattern 
                ORDER BY invoice_no DESC 
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':pattern', $likePattern);
        $stmt->execute();
        
        $nextSerial = 1; // Default to 1 if no invoices found
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $maxInvoice = $row['invoice_no'];
            
            // Debug: Log found invoice
            error_log("Found invoice: {$maxInvoice}");
            
            if ($maxInvoice) {
                // Extract serial number from invoice format: TIF-MON-YY-XXXX-DD
                $parts = explode('-', $maxInvoice);
                
                // Debug: Log parts
                error_log("Invoice parts: " . print_r($parts, true));
                
                if (count($parts) >= 4) {
                    $lastSerial = intval($parts[3] ?? 0);
                    $nextSerial = $lastSerial + 1;
                    error_log("Extracted serial: {$lastSerial}, Next: {$nextSerial}");
                }
            }
        } else {
            error_log("No invoices found for pattern: {$likePattern}");
        }
        
        // Alternative approach if first method didn't work
        if ($nextSerial == 1) {
            $sql2 = "SELECT invoice_no FROM invoices WHERE invoice_no LIKE :pattern ORDER BY invoice_no DESC";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->bindValue(':pattern', $likePattern);
            $stmt2->execute();
            
            $serials = [];
            while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                $inv = $row2['invoice_no'];
                $parts = explode('-', $inv);
                if (count($parts) >= 4) {
                    $serial = intval($parts[3]);
                    $serials[] = $serial;
                }
            }
            
            if (!empty($serials)) {
                $maxSerial = max($serials);
                $nextSerial = $maxSerial + 1;
                error_log("Alternative method: Max serial = {$maxSerial}, Next = {$nextSerial}");
            }
        }
        
        echo json_encode([
            'success' => true,
            'nextSerial' => $nextSerial,
            'year' => $year,
            'month' => $month,
            'monthAbbr' => $monthAbbr,
            'pattern' => $likePattern
        ]);
        
    } catch (Exception $e) {
        error_log("Error in get-next-serial.php: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'nextSerial' => 1 // Fallback
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>