-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 22, 2025 at 12:58 AM
-- Server version: 10.11.15-MariaDB
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `travhub_invoice`
--

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `client_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`client_info`)),
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount_in_words` text DEFAULT NULL,
  `work_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`work_items`)),
  `vendor_payment_methods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vendor_payment_methods`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `client_title` varchar(255) GENERATED ALWAYS AS (json_unquote(json_extract(`client_info`,'$.title'))) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_no`, `date`, `client_info`, `total_amount`, `paid_amount`, `due_amount`, `total_amount_in_words`, `work_items`, `vendor_payment_methods`, `created_at`, `updated_at`) VALUES
(1, 'TIF-DEC-25-0001-14', '2025-12-14', '{\"title\":\"Radiant Business Consortium Limited\",\"phone_no\":\"+880 1521-442923\",\"cc\":\"Sabbir Sarker\"}', 70500.00, 0.00, 70500.00, 'Seventy Thousand Five Hundred Taka Only', '[{\"title\":\"Hotel at Taipei\",\"qty\":3,\"rate\":23500,\"particular\":\"The Howard Plaza Hotel Taipei\\r\\nGuest Name: REZWANUR RAHMAN\\r\\nC\\/In: 02 Dec 25 | C\\/Out: 6 Dec 25\\r\\nRoom:Standard Double Room\\r\\nWith Breakfast\\r\\n2 Adults | 3 Nights\",\"amount\":70500}]', '{\"banks\":[],\"mfs\":[]}', '2025-12-14 11:24:53', '2025-12-14 11:24:53'),
(2, 'TIF-DEC-25-0002-14', '2025-12-14', '{\"title\":\"Asif M Sazid\",\"phone_no\":\"01751906710\",\"cc\":\"asif83sazid@gmail.com\"}', 12000.00, 10000.00, 2000.00, 'Twelve Thousand Taka Only', '[{\"title\":\"Air Ticket\",\"qty\":1,\"rate\":12000,\"particular\":\"\",\"amount\":12000}]', '{\"banks\":[],\"mfs\":[]}', '2025-12-14 11:54:29', '2025-12-14 11:54:29'),
(3, 'TIF-DEC-25-0003-15', '2025-12-15', '{\"title\":\"HOWLADER MOHAMMAD JULHAS\",\"phone_no\":\"+880 1841-296410\",\"cc\":\"NOOR E ALAM SIDDIQUEE\"}', 100800.00, 0.00, 100800.00, 'One Lakh Eight Hundred Taka Only', '[{\"title\":\"Hotel At Canada\",\"qty\":6,\"rate\":16800,\"particular\":\"Sandman Hotel &amp; Suites Calgary South\\r\\nCHECK IN - Tue 18 Nov 2025\\r\\nCHECK OUT - Fri 21 Nov 2025\\r\\n3 Nights | 2 Adults | 1 Room\\r\\nGRAND Room, 2 Queen Beds\\r\\nNo meals included\\r\\nGuest Names: HOWLADER MOHAMMAD JULHAS; NOOR E ALAM SIDDIQUEE\",\"amount\":100800}]', '{\"banks\":[],\"mfs\":[]}', '2025-12-15 10:41:40', '2025-12-15 10:41:40'),
(4, 'TIF-DEC-25-0004-18', '2025-12-18', '{\"title\":\"Md Asif Inzamam\",\"phone_no\":\"+880 1755-534994\",\"cc\":\"\"}', 364500.00, 0.00, 364500.00, 'Three Lakh Sixty-Four Thousand Five Hundred Taka Only', '[{\"title\":\"Malaysia E-Visa 6Months Single Entry\",\"qty\":5,\"rate\":5500,\"particular\":\"Travelers&#039; Names: MD ASIF INZAMAM; MD SHARIFUL ALAM; MST SHOBNUM ALAM; LAILA BANU; FARIDA BEGUM\",\"amount\":27500},{\"title\":\"Air Ticket on Biman Bangladesh\",\"qty\":5,\"rate\":56200,\"particular\":\"DAC-KUL-DAC\\/\\/5Adults\\/\\/FYDIVM\\r\\nTr. Dt: 05 Jan | Rt. Dt: 11 Jan\",\"amount\":281000},{\"title\":\"Air Ticket on Batik Air (Malaysia Domestic)\",\"qty\":5,\"rate\":6000,\"particular\":\"KUL-LGK\\/\\/5Pax\\/\\/CLIDLM\\r\\nBaggage: 15Kg\\r\\nTr. Dt: 6 Jan\",\"amount\":30000},{\"title\":\"Air Ticket on AirAsia (Malaysia Domestic)\",\"qty\":5,\"rate\":5200,\"particular\":\"LGK-KUL\\/\\/5Pax\\/\\/DYNSPM\\r\\nBaggage: 15Kg\\r\\nTr. Dt: 8 Jan\",\"amount\":26000}]', '{\"banks\":[],\"mfs\":[]}', '2025-12-18 06:16:14', '2025-12-18 06:16:14'),
(5, 'TIF-DEC-25-0005-20', '2025-12-20', '{\"title\":\"Bonafide Knitting Mills Limited\",\"phone_no\":\"+880 1836-744966\",\"cc\":\"Sadikuz Zaman\"}', 834980.00, 0.00, 834980.00, 'Eight Lakh Thirty-Four Thousand Nine Hundred and Eighty Taka Only', '[{\"title\":\"China Visa\",\"qty\":2,\"rate\":65000,\"particular\":\"2 Years Multiple Entry (Contact)\\r\\nBusiness Visa\\r\\nApplicants: Sadikuz Zaman; Wahiduzzaman\",\"amount\":130000},{\"title\":\"Dhaka - Shanghai - Dhaka Ticket (Refund)\",\"qty\":3,\"rate\":11660,\"particular\":\"Dhaka - Shanghai - Dhaka on Chaina Eastern (MU)\\r\\nTr. Dt: 15 Dec | Rt. Dt: 19 Dec\\r\\nTravellers: Wahiduzzaman, Sadikuz Zaman, Md Humayun Kabir\\r\\nNote: Refund Charge 90 USD PP + Service Charge on Refund BDT 500 PP\",\"amount\":34980},{\"title\":\"Madina Hilton\",\"qty\":1,\"rate\":670000,\"particular\":\"3 Rooms | Half Board (Sehari and Iftar)\\r\\n2 Rooms late Checkout (Till Iftar)\\r\\nAdditional Iftar for 7 person on 2nd March.\",\"amount\":670000}]', '{\"banks\":[],\"mfs\":[]}', '2025-12-20 10:09:01', '2025-12-20 10:09:01'),
(6, 'TIF-DEC-25-0006-20', '2025-12-20', '{\"title\":\"Moman Construction Ltd\",\"phone_no\":\"+880 1878-877769\",\"cc\":\"Ahmad Mahdi\"}', 1304350.00, 395000.00, 909350.00, 'Thirteen Lakh Four Thousand Three Hundred and Fifty Taka Only', '[{\"title\":\"Hotel at Rome | Hotel Stromboli\",\"qty\":1,\"rate\":19000,\"particular\":\"C\\/In: 13 Dec | C\\/Out: 14 Dec | Tripple\"},{\"title\":\"Hotel at Rome | Hotel Stromboli\",\"qty\":1,\"rate\":21000,\"particular\":\"C\\/In: 13 Dec | C\\/Out: 14 Dec | Double | With Breakfast\"},{\"title\":\"Hotel at Venice | Hotel Casanova Venezia\",\"qty\":1,\"rate\":18000,\"particular\":\"C\\/In: 14 Dec | C\\/Out: 15 Dec | Superior Double | With Breakfast\"},{\"title\":\"Hotel at Venice | Hotel Casanova Venezia\",\"qty\":1,\"rate\":18000,\"particular\":\"C\\/In: 14 Dec | C\\/Out: 15 Dec | Superior Twin | With Breakfast\"},{\"title\":\"Hotel at Venice | Hotel Casanova Venezia\",\"qty\":1,\"rate\":15500,\"particular\":\"C\\/In: 14 Dec | C\\/Out: 15 Dec | Single Room | With Breakfast\"},{\"title\":\"Hotel at Milan | Scarlatti Hotel Milano\",\"qty\":1,\"rate\":21000,\"particular\":\"C\\/In: 15 Dec | C\\/Out: 16 Dec | King Room City View | With Breakfast\"},{\"title\":\"Hotel at Milan | Scarlatti Hotel Milano\",\"qty\":2,\"rate\":15700,\"particular\":\"C\\/In: 15 Dec | C\\/Out: 16 Dec | Standard Single | With Breakfast\"},{\"title\":\"Hotel at Interlaken | Hotel Krebs\",\"qty\":2,\"rate\":32000,\"particular\":\"C\\/In: 16 Dec | C\\/Out: 18 Dec | Junior Suite | With Breakfast\"},{\"title\":\"Hotel at Interlaken | Hotel Krebs\",\"qty\":2,\"rate\":25000,\"particular\":\"C\\/In: 16 Dec | C\\/Out: 18 Dec | Single Room | With Breakfast\"},{\"title\":\"Hotel at Interlaken | Hotel Krebs\",\"qty\":2,\"rate\":42500,\"particular\":\"C\\/In: 16 Dec | C\\/Out: 18 Dec | Standard Double (Twin) | With Breakfast\"},{\"title\":\"Hotel at Zurich | Sorell Hotel Rutli\",\"qty\":1,\"rate\":31500,\"particular\":\"C\\/In: 18 Dec | C\\/Out: 19 Dec | Standard King | With Breakfast\"},{\"title\":\"Hotel at Zurich | Sorell Hotel Rutli\",\"qty\":1,\"rate\":25500,\"particular\":\"C\\/In: 18 Dec | C\\/Out: 19 Dec | Standard Single | With Breakfast\"},{\"title\":\"Hotel at Zurich | Sorell Hotel Rutli\",\"qty\":1,\"rate\":35000,\"particular\":\"C\\/In: 18 Dec | C\\/Out: 19 Dec | Standard Twin | With Breakfast\"},{\"title\":\"Hotel at Munich | Hotel Condor\",\"qty\":1,\"rate\":19500,\"particular\":\"C\\/In: 19 Dec | C\\/Out: 20 Dec | Superior Single | With Breakfast\"},{\"title\":\"Hotel at Munich | Hotel Condor\",\"qty\":2,\"rate\":16000,\"particular\":\"C\\/In: 19 Dec | C\\/Out: 20 Dec | Standard Single | With Breakfast\"},{\"title\":\"Hotel at Milan | Scarlatti Hotel Milano\",\"qty\":1,\"rate\":21000,\"particular\":\"C\\/In: 19 Dec | C\\/Out: 20 Dec | King Room City View | With Breakfast\"},{\"title\":\"Hotel at Milan | Scarlatti Hotel Milano\",\"qty\":2,\"rate\":15700,\"particular\":\"C\\/In: 19 Dec | C\\/Out: 20 Dec | Standard Room | With Breakfast\"},{\"title\":\"Zurich To Milan Train Tickets (Iftekhar Sir)\",\"qty\":1,\"rate\":20500,\"particular\":\"\"},{\"title\":\"Zurich To Milan Train Tickets\",\"qty\":2,\"rate\":18300,\"particular\":\"\"},{\"title\":\"Zurich to Munich Train Tickets\",\"qty\":3,\"rate\":22000,\"particular\":\"\"},{\"title\":\"Spiez To Interlaken Train Ticket\",\"qty\":3,\"rate\":2200,\"particular\":\"\"},{\"title\":\"Swiss Travel Pass for 3 Days | First Class\",\"qty\":3,\"rate\":74000,\"particular\":\"\"},{\"title\":\"Air Ticket (Emirates) | Refund Charge\",\"qty\":3,\"rate\":36750,\"particular\":\"Airlines Refund Charge 250 Euro\\r\\nService Charge BDT 500\"},{\"title\":\"Milan (MXP) - Dhaka (DAC) on Qatar Airways\",\"qty\":3,\"rate\":101200,\"particular\":\"Tr. Dt: 20 Dec\\r\\nPassengers: IFTAKHAR\\/MD SHAMIM, ISLAM\\/MOHAMMAD MONJURUL, MASHFIQ\\/NAYEEM MOHAMMAD\"}]', 'null', '2025-12-20 14:12:00', '2025-12-21 14:10:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `idx_invoice_no` (`invoice_no`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_client_title` (`client_title`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
