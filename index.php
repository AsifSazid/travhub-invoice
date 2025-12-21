<?php
require 'server/db_connection.php'; // your PDO connection

try {
    // Load invoices from database
    $stmt = $pdo->query("
        SELECT * FROM invoices
        ORDER BY created_at DESC
    ");

    $invoices = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Decode client_info JSON
        $client_info = json_decode($row['client_info'], true);

        // Calculate status based on paid_amount and due_amount
        $status = 'pending';
        if ($row['due_amount'] == 0) {
            $status = 'paid';
        } elseif ($row['paid_amount'] == 0) {
            $status = 'pending';
        } elseif ($row['due_amount'] > 0) {
            // Check if invoice is overdue (for demo, we'll use 30 days from creation)
            $created_date = new DateTime($row['created_at']);
            $due_date = $created_date->modify('+30 days');
            $now = new DateTime();

            if ($now > $due_date) {
                $status = 'overdue';
            } else {
                $status = 'pending';
            }
        }

        // Decode work items
        $work_items = json_decode($row['work_items'], true) ?: [];

        $invoices[] = [
            "id" => $row['id'],
            "invoice_no" => $row['invoice_no'],
            "client_name" => $client_info['title'] ?? 'Unknown Client',
            "client_email" => $client_info['cc'] ?? '',
            "phone" => $client_info['phone_no'] ?? '',
            "total_amount" => floatval($row['total_amount']),
            "paid_amount" => floatval($row['paid_amount']),
            "due_amount" => floatval($row['due_amount']),
            "created_at" => $row['created_at'],
            "updated_at" => $row['updated_at'],
            "invoice_date" => $row['date'], // Use the date field from database
            "due_date" => date('Y-m-d', strtotime($row['date'] . ' +30 days')), // Assuming 30-day payment term
            "status" => $status,
            "currency" => "BDT", // You can store this in database if needed
            "description" => "Visa Application Services", // Default description or extract from work items
            "amount" => floatval($row['total_amount']), // For compatibility with existing JS
            "items" => array_map(function ($item) {
                return [
                    'description' => $item['title'] ?? 'Service',
                    'quantity' => intval($item['qty'] ?? 1),
                    'unit_price' => floatval($item['rate'] ?? 0),
                    'total' => floatval($item['amount'] ?? 0)
                ];
            }, $work_items)
        ];
    }

    // var_dump($invoices); // Uncomment for debugging
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Management Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .invoice-card {
            transition: all 0.3s ease;
            border-left: 5px solid transparent;
        }

        .invoice-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .status-pending {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            box-shadow: 0 2px 5px rgba(245, 158, 11, 0.2);
        }

        .status-paid {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 2px 5px rgba(16, 185, 129, 0.2);
        }

        .status-overdue {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 2px 5px rgba(239, 68, 68, 0.2);
        }

        .status-draft {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
            box-shadow: 0 2px 5px rgba(107, 114, 128, 0.2);
        }

        .amount-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-weight: 700;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            box-shadow: 0 2px 5px rgba(59, 130, 246, 0.2);
        }

        .action-btn {
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .invoice-type-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
        }

        .type-visa {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        .type-ticket {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            color: #0c4a6e;
            border: 1px solid #7dd3fc;
        }

        .type-service {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #166534;
            border: 1px solid #86efac;
        }

        .type-other {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .line-clamp-2 {
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Header -->
        <header class="text-center mb-12">
            <div class="flex flex-col items-center mb-8">
                <div class="relative mb-6">
                    <div class="w-24 h-24 bg-gradient-to-r from-green-500 via-green-600 to-emerald-700 rounded-full flex items-center justify-center mb-4 shadow-xl">
                        <i class="fas fa-file-invoice-dollar text-white text-4xl"></i>
                    </div>
                    <div class="absolute -bottom-2 -right-2 w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center shadow-lg">
                        <i class="fas fa-receipt text-white text-lg"></i>
                    </div>
                </div>
                <h1 class="text-4xl font-bold text-gray-800 mb-3 bg-gradient-to-r from-green-600 to-emerald-800 bg-clip-text text-transparent">Invoice Management</h1>
                <p class="text-gray-600 max-w-2xl text-lg">Generate, manage and download professional invoices for visa applications and services.</p>
            </div>
        </header>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-center">
                    <div class="w-14 h-14 bg-gradient-to-r from-green-100 to-green-200 rounded-xl flex items-center justify-center mr-5">
                        <i class="fas fa-file-invoice text-green-600 text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Total Invoices</p>
                        <h3 id="total-invoices" class="text-3xl font-bold text-gray-800 mt-1">0</h3>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-center">
                    <div class="w-14 h-14 bg-gradient-to-r from-blue-100 to-blue-200 rounded-xl flex items-center justify-center mr-5">
                        <i class="fas fa-money-bill-wave text-blue-600 text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Total Revenue</p>
                        <h3 id="total-revenue" class="text-3xl font-bold text-gray-800 mt-1">৳ 0</h3>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-center">
                    <div class="w-14 h-14 bg-gradient-to-r from-amber-100 to-amber-200 rounded-xl flex items-center justify-center mr-5">
                        <i class="fas fa-clock text-amber-600 text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Pending</p>
                        <h3 id="pending-invoices" class="text-3xl font-bold text-gray-800 mt-1">0</h3>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-center">
                    <div class="w-14 h-14 bg-gradient-to-r from-red-100 to-red-200 rounded-xl flex items-center justify-center mr-5">
                        <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Overdue</p>
                        <h3 id="overdue-invoices" class="text-3xl font-bold text-gray-800 mt-1">0</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices Dashboard -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-200 mb-16">
            <div class="px-8 py-7 border-b border-gray-200 bg-gradient-to-r from-green-50/80 to-gray-50/80 backdrop-blur-sm">
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center">
                    <div class="mb-6 lg:mb-0">
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Generated Invoices</h2>
                        <p class="text-gray-600">Manage and download all your invoices in one place</p>
                    </div>
                    <div class="flex flex-wrap gap-4">
                        <button id="refresh-btn" class="bg-white hover:bg-gray-50 text-gray-800 font-medium py-3 px-5 rounded-xl transition duration-300 flex items-center shadow-sm border border-gray-200 action-btn">
                            <i class="fas fa-sync-alt mr-3"></i> Refresh
                        </button>
                        <a href="create.php" id="create-invoice" class="bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-700 hover:to-emerald-800 text-white font-medium py-3 px-6 rounded-xl transition duration-300 flex items-center shadow-lg action-btn">
                            <i class="fas fa-plus mr-3"></i> Create Invoice
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <div class="mt-8 flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="filter-status" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>

                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                        <select id="filter-date" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">All Time</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="quarter">This Quarter</option>
                        </select>
                    </div>

                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
                        <select id="filter-amount" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">All Amounts</option>
                            <option value="0-10000">৳ 0 - ৳ 10,000</option>
                            <option value="10000-50000">৳ 10,000 - ৳ 50,000</option>
                            <option value="50000-100000">৳ 50,000 - ৳ 100,000</option>
                            <option value="100000+">৳ 100,000+</option>
                        </select>
                    </div>

                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" id="search-invoice" placeholder="Search by invoice no, name..." class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            <div class="p-6 lg:p-8">
                <div id="invoices-list" class="space-y-6">
                    <!-- Invoices will be listed here -->
                </div>

                <div id="no-invoices" class="text-center py-20 hidden">
                    <div class="max-w-lg mx-auto">
                        <div class="w-40 h-40 mx-auto mb-8 relative">
                            <div class="absolute inset-0 bg-gradient-to-r from-green-100 to-gray-100 rounded-full blur-xl opacity-50"></div>
                            <div class="relative w-full h-full bg-gradient-to-br from-green-50 to-gray-50 rounded-full flex items-center justify-center border-2 border-dashed border-gray-300">
                                <i class="fas fa-file-invoice-dollar text-gray-400 text-6xl"></i>
                            </div>
                        </div>
                        <h3 class="text-2xl font-semibold text-gray-600 mb-3">No invoices found</h3>
                        <p class="text-gray-500 mb-8 max-w-md mx-auto">Start by creating your first invoice for visa applications or services.</p>
                        <a href="create.php" class="bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-700 hover:to-emerald-800 text-white font-medium py-4 px-10 rounded-xl transition duration-300 shadow-lg action-btn text-lg inline-flex items-center">
                            <i class="fas fa-plus mr-3"></i> Create First Invoice
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-20 pt-10 border-t border-gray-200">
            <div class="flex flex-col lg:flex-row justify-between items-center">
                <div class="mb-8 lg:mb-0">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-600 to-emerald-800 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                            <i class="fas fa-globe-asia text-white text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800 text-lg">TravHub Global Limited</h4>
                            <p class="text-sm text-gray-500">Professional Billing & Invoice Services</p>
                        </div>
                    </div>
                </div>
                <div class="text-center lg:text-right">
                    <p class="text-gray-500 text-sm mb-2">© 2025 TravHub Global Limited. All rights reserved.</p>
                    <p class="text-gray-400 text-xs">Invoice Management System for Visa Applications</p>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Invoice data structure - Now properly formatted
        let invoices = <?php echo json_encode($invoices, JSON_PRETTY_PRINT); ?>;
        let itemCounter = 1;

        // Initialize the dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadInvoices();
            setupEventListeners();
        });

        // Set up event listeners
        function setupEventListeners() {
            document.getElementById('refresh-btn').addEventListener('click', loadInvoices);

            // Filter event listeners
            ['filter-status', 'filter-date', 'filter-amount'].forEach(id => {
                document.getElementById(id).addEventListener('change', filterInvoices);
            });

            // Search listener
            document.getElementById('search-invoice').addEventListener('input', filterInvoices);
        }

        // Load all invoices
        function loadInvoices() {
            // You can add AJAX call here to refresh from server
            // For now, we'll just use the PHP-loaded data
            renderInvoices();
            updateStats();
        }

        // Send via Email
        function sendEmail(invoiceId, email) {
            if (!email) {
                alert('No email address found for this client.');
                return;
            }

            if (confirm(`Send invoice to ${email}?`)) {
                fetch('send_invoice.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            invoice_id: invoiceId,
                            email: email,
                            method: 'email'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Invoice sent via email successfully!');
                        } else {
                            alert('Error sending invoice: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('Error sending invoice: ' + error.message);
                    });
            }
        }

        // Send via WhatsApp
        function sendWhatsApp(invoiceId, phone) {
            if (!phone) {
                alert('No phone number found for this client.');
                return;
            }

            // Clean phone number (remove spaces, +, etc.)
            const cleanPhone = phone.replace(/[\s\+]/g, '');

            // Get invoice details first
            const invoice = invoices.find(inv => inv.id == invoiceId);
            if (!invoice) {
                alert('Invoice not found!');
                return;
            }

            // Create WhatsApp message
            const message = `Hello! Here is your invoice ${invoice.invoice_no}.\n` +
                `Amount: ${invoice.currency || 'BDT'} ${invoice.total_amount.toFixed(2)}\n` +
                `You can download it here: ${window.location.origin}/print-invoice.php?id=${invoiceId}\n` +
                `Thank you!`;

            // URL encode the message
            const encodedMessage = encodeURIComponent(message);

            // Create WhatsApp URL
            const whatsappUrl = `https://wa.me/${cleanPhone}?text=${encodedMessage}`;

            if (confirm(`Send invoice via WhatsApp to ${phone}?`)) {
                // Open WhatsApp in new tab
                window.open(whatsappUrl, '_blank');

                // Optional: Log in your system
                logWhatsAppSend(invoiceId, phone);
            }
        }

        // Log WhatsApp send (optional)
        function logWhatsAppSend(invoiceId, phone) {
            fetch('log_whatsapp_send.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        invoice_id: invoiceId,
                        phone: phone,
                        timestamp: new Date().toISOString()
                    })
                })
                .catch(error => console.error('Error logging WhatsApp send:', error));
        }

        // Send invoice with options modal
        function sendInvoiceWithOptions(invoiceId, email, phone) {
            const invoice = invoices.find(inv => inv.id == invoiceId);
            if (!invoice) {
                alert('Invoice not found!');
                return;
            }

            // Create modal
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center p-4 z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full">
                    <div class="p-7 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-gray-50">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-purple-600 to-purple-700 rounded-xl flex items-center justify-center mr-5">
                                <i class="fas fa-paper-plane text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">Send Invoice</h3>
                                <p class="text-gray-600 text-sm mt-1">${invoice.invoice_no}</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-7">
                        <div class="space-y-4">
                            ${email ? `
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-r from-purple-100 to-purple-200 rounded-lg flex items-center justify-center mr-4">
                                            <i class="fas fa-envelope text-purple-600"></i>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-800">Email</div>
                                            <div class="text-sm text-gray-600">${email}</div>
                                        </div>
                                    </div>
                                    <button onclick="sendEmail('${invoiceId}', '${email}'); this.closest('.fixed').remove()" 
                                            class="bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg transition duration-300">
                                        Send
                                    </button>
                                </div>
                            ` : ''}
                            
                            ${phone ? `
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-r from-green-100 to-green-200 rounded-lg flex items-center justify-center mr-4">
                                            <i class="fab fa-whatsapp text-green-600"></i>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-800">WhatsApp</div>
                                            <div class="text-sm text-gray-600">${phone}</div>
                                        </div>
                                    </div>
                                    <button onclick="sendWhatsApp('${invoiceId}', '${phone}'); this.closest('.fixed').remove()" 
                                            class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg transition duration-300">
                                        Send
                                    </button>
                                </div>
                            ` : ''}
                            
                            ${!email && !phone ? `
                                <div class="text-center py-8">
                                    <i class="fas fa-exclamation-circle text-gray-400 text-4xl mb-4"></i>
                                    <p class="text-gray-600">No contact information available for this client.</p>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    <div class="p-7 border-t border-gray-200 bg-gray-50 flex justify-end">
                        <button onclick="this.closest('.fixed').remove()" 
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-3 px-6 rounded-xl transition duration-300">
                            Cancel
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Render the invoices list
        function renderInvoices(filteredInvoices = null) {
            const invoicesToRender = filteredInvoices || invoices;
            const listContainer = document.getElementById('invoices-list');
            const noInvoices = document.getElementById('no-invoices');

            if (invoicesToRender.length === 0) {
                listContainer.innerHTML = '';
                noInvoices.classList.remove('hidden');
                return;
            }

            noInvoices.classList.add('hidden');

            let html = '';
            invoicesToRender.forEach((invoice, index) => {
                const createdDate = new Date(invoice.created_at);
                const dueDate = new Date(invoice.due_date);
                const invoiceDate = new Date(invoice.invoice_date);
                const now = new Date();

                const formattedDate = createdDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });

                const dueFormatted = dueDate.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric'
                });

                // Determine status and overdue
                let statusClass, statusText, isOverdue = false;

                if (invoice.status === 'paid') {
                    statusClass = 'status-paid';
                    statusText = 'Paid';
                } else if (invoice.status === 'overdue') {
                    statusClass = 'status-overdue';
                    statusText = 'Overdue';
                    isOverdue = true;
                } else if (invoice.status === 'pending') {
                    if (dueDate < now) {
                        statusClass = 'status-overdue';
                        statusText = 'Overdue';
                        isOverdue = true;
                    } else {
                        statusClass = 'status-pending';
                        statusText = 'Pending';
                    }
                } else {
                    statusClass = 'status-pending';
                    statusText = 'Pending';
                }

                // Determine invoice type from description
                let typeClass = 'type-other';
                let typeText = 'Service';
                const desc = invoice.description?.toLowerCase() || '';
                if (desc.includes('visa') || desc.includes('application')) {
                    typeClass = 'type-visa';
                    typeText = 'Visa';
                } else if (desc.includes('ticket') || desc.includes('flight')) {
                    typeClass = 'type-ticket';
                    typeText = 'Ticket';
                } else if (desc.includes('service') || desc.includes('fee')) {
                    typeClass = 'type-service';
                    typeText = 'Service';
                }

                html += `
                    <div class="invoice-card bg-white border border-gray-200 rounded-2xl p-6 hover:border-green-300 fade-in">
                        <div class="flex flex-col lg:flex-row justify-between gap-6">
                            <!-- Left Column -->
                            <div class="flex-1">
                                <!-- Header Section -->
                                <div class="flex flex-col lg:flex-row lg:items-start justify-between mb-6">
                                    <div class="mb-4 lg:mb-0">
                                        <div class="flex items-center mb-3">
                                            <div class="w-12 h-12 bg-gradient-to-r from-green-100 to-green-200 rounded-xl flex items-center justify-center mr-4">
                                                <i class="fas fa-file-invoice text-green-600 text-xl"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-gray-800 text-xl">
                                                    ${invoice.client_name || 'Unnamed Client'}
                                                </h3>
                                                <div class="flex items-center mt-2">
                                                    <span class="text-gray-600 text-sm mr-4">
                                                        <i class="far fa-calendar mr-1"></i> ${formattedDate}
                                                    </span>
                                                    <span class="text-gray-600 text-sm ${isOverdue ? 'text-red-600 font-medium' : ''}">
                                                        <i class="far fa-clock mr-1"></i> Due: ${dueFormatted}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600">
                                            <span class="flex items-center bg-gray-100 px-3 py-1.5 rounded-full">
                                                <i class="fas fa-hashtag mr-2"></i> ${invoice.invoice_no}
                                            </span>
                                            <span class="invoice-type-badge ${typeClass}">
                                                ${typeText}
                                            </span>
                                            ${invoice.phone ? `
                                                <span class="flex items-center bg-gray-100 px-3 py-1.5 rounded-full">
                                                    <i class="fas fa-phone mr-2"></i> ${invoice.phone}
                                                </span>
                                            ` : ''}
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col items-end">
                                        <div class="amount-badge mb-3">
                                            ${invoice.currency || 'BDT'} ${invoice.total_amount.toFixed(2)}
                                        </div>
                                        <span class="status-badge ${statusClass}">${statusText}</span>
                                    </div>
                                </div>
                                
                                <!-- Payment Summary -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div class="bg-green-50 p-3 rounded-lg border border-green-100">
                                        <div class="text-sm text-green-700 mb-1">Total Amount</div>
                                        <div class="text-lg font-bold text-green-800">৳ ${invoice.total_amount.toFixed(2)}</div>
                                    </div>
                                    <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                                        <div class="text-sm text-blue-700 mb-1">Paid Amount</div>
                                        <div class="text-lg font-bold text-blue-800">৳ ${invoice.paid_amount.toFixed(2)}</div>
                                    </div>
                                    <div class="bg-red-50 p-3 rounded-lg border border-red-100">
                                        <div class="text-sm text-red-700 mb-1">Due Amount</div>
                                        <div class="text-lg font-bold text-red-800">৳ ${invoice.due_amount.toFixed(2)}</div>
                                    </div>
                                </div>
                                
                                <!-- Items Preview -->
                                ${invoice.items && invoice.items.length > 0 ? `
                                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                                        <div class="text-sm text-gray-500 mb-2">Items (${invoice.items.length})</div>
                                        <div class="space-y-2">
                                            ${invoice.items.slice(0, 2).map(item => `
                                                <div class="flex justify-between items-center text-sm">
                                                    <span class="text-gray-700">${item.description || 'Item'}</span>
                                                    <span class="text-gray-800 font-medium">${invoice.currency || 'BDT'} ${item.total.toFixed(2)}</span>
                                                </div>
                                            `).join('')}
                                            ${invoice.items.length > 2 ? `
                                                <div class="text-center text-sm text-gray-500 pt-2 border-t border-gray-200">
                                                    +${invoice.items.length - 2} more items
                                                </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="lg:w-64 flex flex-col space-y-3">
                                <button class="download-btn bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-medium py-3.5 px-5 rounded-xl transition duration-300 flex items-center justify-center action-btn shadow-md"
                                        onclick="downloadInvoice('${invoice.id}')" 
                                        title="Download PDF">
                                    <i class="fas fa-download mr-3"></i>
                                    <span>Download PDF</span>
                                </button>
                                <button class="view-btn bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-medium py-3.5 px-5 rounded-xl transition duration-300 flex items-center justify-center action-btn"
                                        onclick="editInvoice('${invoice.id}')" 
                                        title="Edit Invoice">
                                    <i class="fas fa-pencil mr-3"></i>
                                    <span>Edit Invoice</span>
                                </button>
                                <button class="send-btn bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 font-medium py-3.5 px-5 rounded-xl transition duration-300 flex items-center justify-center action-btn"
                                        onclick="sendInvoiceWithOptions('${invoice.id}', '${invoice.client_email}', '${invoice.phone}')" 
                                        title="Send Invoice">
                                    <i class="fas fa-paper-plane mr-3"></i>
                                    <span>Send Invoice</span>
                                </button>
                                ${invoice.status !== 'paid' ? `
                                    <button class="mark-paid-btn bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 font-medium py-3.5 px-5 rounded-xl transition duration-300 flex items-center justify-center action-btn"
                                            onclick="markAsPaid('${invoice.id}')" 
                                            title="Mark as Paid">
                                        <i class="fas fa-check-circle mr-3"></i>
                                        <span>Mark as Paid</span>
                                    </button>
                                ` : ''}
                                <button class="delete-btn bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-medium py-3.5 px-5 rounded-xl transition duration-300 flex items-center justify-center action-btn"
                                        onclick="deleteInvoice('${invoice.id}')" 
                                        title="Delete Invoice">
                                    <i class="fas fa-trash mr-3"></i>
                                    <span>Delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            listContainer.innerHTML = html;
        }

        // Filter invoices
        function filterInvoices() {
            const statusFilter = document.getElementById('filter-status').value;
            const dateFilter = document.getElementById('filter-date').value;
            const amountFilter = document.getElementById('filter-amount').value;
            const searchFilter = document.getElementById('search-invoice').value.toLowerCase();

            let filteredInvoices = invoices;

            // Status filter
            if (statusFilter) {
                filteredInvoices = filteredInvoices.filter(inv => inv.status === statusFilter);
            }

            // Date filter
            if (dateFilter) {
                const now = new Date();
                const startDate = new Date();

                switch (dateFilter) {
                    case 'today':
                        startDate.setHours(0, 0, 0, 0);
                        break;
                    case 'week':
                        startDate.setDate(now.getDate() - 7);
                        break;
                    case 'month':
                        startDate.setMonth(now.getMonth() - 1);
                        break;
                    case 'quarter':
                        startDate.setMonth(now.getMonth() - 3);
                        break;
                }

                filteredInvoices = filteredInvoices.filter(inv => {
                    const invDate = new Date(inv.created_at);
                    return invDate >= startDate;
                });
            }

            // Amount filter
            if (amountFilter) {
                const [min, max] = amountFilter.split('-').map(val => {
                    if (val.endsWith('+')) {
                        return parseFloat(val.slice(0, -1));
                    }
                    return parseFloat(val.replace(/[^0-9.]/g, ''));
                });

                filteredInvoices = filteredInvoices.filter(inv => {
                    const amount = inv.total_amount;
                    if (amountFilter.endsWith('+')) {
                        return amount >= min;
                    } else {
                        return amount >= min && amount <= max;
                    }
                });
            }

            // Search filter
            if (searchFilter) {
                filteredInvoices = filteredInvoices.filter(inv => {
                    return (
                        (inv.invoice_no && inv.invoice_no.toLowerCase().includes(searchFilter)) ||
                        (inv.client_name && inv.client_name.toLowerCase().includes(searchFilter)) ||
                        (inv.client_email && inv.client_email.toLowerCase().includes(searchFilter)) ||
                        (inv.description && inv.description.toLowerCase().includes(searchFilter))
                    );
                });
            }

            renderInvoices(filteredInvoices);
        }

        // Update dashboard statistics
        function updateStats() {
            const totalInvoices = invoices.length;
            const paidInvoices = invoices.filter(inv => inv.status === 'paid').length;
            const pendingInvoices = invoices.filter(inv => inv.status === 'pending').length;
            const overdueInvoices = invoices.filter(inv => inv.status === 'overdue').length;

            // Calculate total revenue (sum of total_amount of all invoices)
            let totalRevenue = 0;
            invoices.forEach(inv => {
                totalRevenue += inv.total_amount;
            });

            document.getElementById('total-invoices').textContent = totalInvoices;
            document.getElementById('pending-invoices').textContent = pendingInvoices + overdueInvoices;
            document.getElementById('overdue-invoices').textContent = overdueInvoices;
            document.getElementById('total-revenue').textContent = `৳ ${totalRevenue.toFixed(2)}`;
        }

        // Download invoice as PDF
        function downloadInvoice(invoiceId) {
            // Redirect to print-invoice.php
            window.open(`print-invoice.php?id=${invoiceId}`, '_blank');
        }

        // View invoice details
        function editInvoice(invoiceId) {
            // You can create a view page or use the print page
            window.open(`edit.php?id=${invoiceId}`);
        }

        // Send invoice to client
        function sendInvoice(invoiceId, email) {
            if (!email) {
                alert('No email address found for this client.');
                return;
            }

            if (confirm(`Send invoice to ${email}?`)) {
                // AJAX call to send email
                fetch('send_invoice.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            invoice_id: invoiceId,
                            email: email
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Invoice sent successfully!');
                        } else {
                            alert('Error sending invoice: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('Error sending invoice: ' + error.message);
                    });
            }
        }

        // Mark invoice as paid
        function markAsPaid(invoiceId) {
            if (confirm(`Mark this invoice as paid?`)) {
                // Find invoice
                const invoiceIndex = invoices.findIndex(inv => inv.id == invoiceId);
                if (invoiceIndex !== -1) {
                    // Update local data
                    invoices[invoiceIndex].status = 'paid';
                    invoices[invoiceIndex].paid_amount = invoices[invoiceIndex].total_amount;
                    invoices[invoiceIndex].due_amount = 0;

                    // Update in database via AJAX
                    updateInvoiceStatus(invoiceId, 'paid', invoices[invoiceIndex].total_amount);

                    // Refresh display
                    renderInvoices();
                    updateStats();

                    alert('Invoice marked as paid!');
                }
            }
        }

        // Delete invoice
        function deleteInvoice(invoiceId) {
            if (confirm(`Are you sure you want to delete this invoice? This action cannot be undone.`)) {
                // Find invoice
                const invoice = invoices.find(inv => inv.id == invoiceId);
                if (!invoice) {
                    alert('Invoice not found!');
                    return;
                }

                // Remove from array
                invoices = invoices.filter(inv => inv.id != invoiceId);

                // Delete from database via AJAX
                deleteInvoiceFromDatabase(invoiceId);

                // Refresh display
                renderInvoices();
                updateStats();

                alert('Invoice deleted successfully!');
            }
        }

        // Update invoice status in database
        function updateInvoiceStatus(invoiceId, status, paidAmount) {
            fetch('update_invoice.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: invoiceId,
                        status: status,
                        paid_amount: paidAmount,
                        due_amount: 0
                    })
                })
                .catch(error => console.error('Error updating invoice:', error));
        }

        // Delete invoice from database
        function deleteInvoiceFromDatabase(invoiceId) {
            fetch('delete_invoice.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: invoiceId
                    })
                })
                .catch(error => console.error('Error deleting invoice:', error));
        }
    </script>
</body>

</html