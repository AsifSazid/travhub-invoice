<?php
// server/invoice-update.php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Get invoice data
    $invoice_id = intval($_POST['invoice_id']);
    $invoice_no = $_POST['invoice_no'];
    $date = $_POST['date'];
    $total_amount = floatval($_POST['total_amount']);
    $paid_amount = floatval($_POST['paid_amount']);
    $due_amount = floatval($_POST['due_amount']);

    // Get client info
    $client_info = [
        'title' => $_POST['client_title'] ?? '',
        'phone_no' => $_POST['client_phone_no'] ?? '',
        'cc' => $_POST['client_cc'] ?? ''
    ];

    // Get work items from arrays
    $work_items = [];
    $work_titles = $_POST['work_title'] ?? [];
    $work_qtys = $_POST['work_qty'] ?? [];
    $work_rates = $_POST['work_rate'] ?? [];
    $work_particulars = $_POST['work_particular'] ?? [];
    $amounts = $_POST['amount'] ?? [];

    // Build work items array
    for ($i = 0; $i < count($work_titles); $i++) {
        if (!empty($work_titles[$i]) || !empty($work_particulars[$i])) {
            $work_items[] = [
                'title' => $work_titles[$i] ?? '',
                'qty' => floatval($work_qtys[$i] ?? 0),
                'rate' => floatval($work_rates[$i] ?? 0),
                'particular' => $work_particulars[$i] ?? ''
            ];
        }
    }

    // Get vendor payment methods
    $vendor_payment_methods = ['banks' => [], 'mfs' => []];

    if (isset($_POST['vendor_payment_methods'])) {
        $vendor_payment_methods = json_decode($_POST['vendor_payment_methods'], true);
    } else {
        // Alternative: get from separate fields if exists
        if (isset($_POST['banks']) && !empty($_POST['banks'])) {
            $vendor_payment_methods['banks'] = json_decode($_POST['banks'], true);
        }
        if (isset($_POST['mfs']) && !empty($_POST['mfs'])) {
            $vendor_payment_methods['mfs'] = json_decode($_POST['mfs'], true);
        }
    }

    // Function to convert number to words
    function numberToWords($number)
    {
        $ones = array(
            0 => 'Zero',
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
            8 => 'Eight',
            9 => 'Nine',
            10 => 'Ten',
            11 => 'Eleven',
            12 => 'Twelve',
            13 => 'Thirteen',
            14 => 'Fourteen',
            15 => 'Fifteen',
            16 => 'Sixteen',
            17 => 'Seventeen',
            18 => 'Eighteen',
            19 => 'Nineteen'
        );

        $tens = array(
            20 => 'Twenty',
            30 => 'Thirty',
            40 => 'Forty',
            50 => 'Fifty',
            60 => 'Sixty',
            70 => 'Seventy',
            80 => 'Eighty',
            90 => 'Ninety'
        );

        if ($number == 0) {
            return 'Zero Taka Only';
        }

        $parts = explode('.', number_format($number, 2, '.', ''));
        $taka = intval($parts[0]);
        $poisha = isset($parts[1]) ? intval($parts[1]) : 0;

        $words = '';

        // Process lakhs
        if ($taka >= 100000) {
            $lakhs = floor($taka / 100000);
            $words .= numberToWordsSimple($lakhs, $ones, $tens) . ' Lakh';
            $taka %= 100000;

            if ($taka > 0) {
                $words .= ' ';
            }
        }

        // Process thousands
        if ($taka >= 1000) {
            $thousands = floor($taka / 1000);
            $words .= numberToWordsSimple($thousands, $ones, $tens) . ' Thousand';
            $taka %= 1000;

            if ($taka > 0) {
                $words .= ' ';
            }
        }

        // Process remaining amount
        if ($taka > 0) {
            $words .= numberToWordsSimple($taka, $ones, $tens);
        }

        if (empty(trim($words))) {
            $words = 'Zero';
        }

        $words = trim($words) . ' Taka';

        // Add poisha if any
        if ($poisha > 0) {
            $words .= ' and ' . numberToWordsSimple($poisha, $ones, $tens) . ' Poisha';
        } else {
            $words .= ' Only';
        }

        return $words;
    }

    function numberToWordsSimple($num, $ones, $tens)
    {
        if ($num == 0) {
            return '';
        }

        $words = '';

        // Hundreds
        if ($num >= 100) {
            $hundreds = floor($num / 100);
            $words .= $ones[$hundreds] . ' Hundred';
            $num %= 100;

            if ($num > 0) {
                $words .= ' and ';
            }
        }

        // Tens and ones
        if ($num >= 20) {
            $tensPart = floor($num / 10) * 10;
            $words .= $tens[$tensPart];
            $num %= 10;

            if ($num > 0) {
                $words .= '-' . $ones[$num];
            }
        } elseif ($num > 0) {
            $words .= $ones[$num];
        }

        return $words;
    }

    // Generate amount in words
    $total_amount_in_words = numberToWords($total_amount);

    // Update invoice
    $stmt = $pdo->prepare("
        UPDATE invoices 
        SET 
            client_info = :client_info,
            total_amount = :total_amount, 
            paid_amount = :paid_amount, 
            due_amount = :due_amount, 
            total_amount_in_words = :words,
            work_items = :work_items, 
            vendor_payment_methods = :vendor_methods,
            updated_at = NOW()
        WHERE id = :invoice_id
    ");
    $stmt->execute([
        ':client_info'      => json_encode($client_info),
        ':total_amount'     => $total_amount,
        ':paid_amount'      => $paid_amount,
        ':due_amount'       => $due_amount,
        ':words'            => $total_amount_in_words,
        ':work_items'       => json_encode($work_items),
        ':vendor_methods'   => json_encode($vendor_payment_methods),
        ':invoice_id'       => $invoice_id
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Invoice updated successfully',
        'invoice_id' => $invoice_id
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
