<?php
// Include database connection
require_once 'dbconnect.php';
require_once 'fpdf/fpdf.php'; // Make sure to have FPDF installed

// Start session
session_start();
if(!isset($_SESSION['user_id'])) { 
    header('Location: login.php'); 
    exit(); 
}

// Check if insurance ID is provided
if(!isset($_GET['id']) || !isset($_GET['policy'])) {
    header('Location: insurance.php');
    exit();
}

$insurance_id = $_GET['id'];
$policy_number = $_GET['policy'];
$user_id = $_SESSION['user_id'];

// Get insurance details
$stmt = $conn->prepare("SELECT ip.*, u.fullname, u.email, u.phone, u.address, u.city, u.state, u.pincode 
                        FROM insurance_payment ip
                        JOIN users u ON ip.user_id = u.id
                        WHERE ip.id = ? AND ip.user_id = ? AND ip.insurance_policy_number = ?");
$stmt->bind_param("iis", $insurance_id, $user_id, $policy_number);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0) {
    // Insurance not found or doesn't belong to user
    header('Location: insurance.php');
    exit();
}

$insurance = $result->fetch_assoc();
$stmt->close();

// Format dates for PDF
$payment_date = new DateTime($insurance['payment_date']);
$expiry_date = new DateTime($insurance['expiry_date']);

// Create PDF class with custom styling
class InsurancePDF extends FPDF {
    function Header() {
        // Company logo - if available
        if(file_exists('image/insu.jpg')) {
            $this->Image('image/insu.jpg', 10, 10, 50);
        } else {
            // If logo not available, use text instead
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(60, 10, 'CLARA CREST WATCHES', 0, 0);
        }
        
        // Rating logo/text on the right
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(204, 0, 0);
        $this->Cell(130, 10, 'AAA', 0, 1, 'R');
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->Cell(190, 5, 'Rating by ICRA', 0, 1, 'R');
        
        // Title centered
        $this->Ln(10);
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(0, 51, 102);
        $this->Cell(0, 10, 'LUXURY WATCH INSURANCE POLICY', 0, 1, 'C');
        $this->SetFont('Arial', '', 11);
        $this->Cell(0, 6, 'Certificate Cum Policy Schedule', 0, 1, 'C');
        
        // Certificate number
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 8, 'Certificate cum Policy No: ' . $GLOBALS['policy_number'], 0, 1, 'C');
        
        // Contact information
        $this->Cell(0, 6, 'For CLAIMS : Call 1800-123-4567 (Toll free from all phones)', 0, 1, 'C');
        $this->Cell(0, 6, 'For RENEWALS : Visit www.claracrest.com or call 1800-123-4567', 0, 1, 'C');
        
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-30);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(0, 5, 'Clara Crest Watches Insurance Ltd.', 0, 1, 'C');
        
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 5, 'Mailing Address: Clara Crest Watches Insurance Ltd., Corporate Office, Mumbai - 400 001', 0, 1, 'C');
        $this->Cell(0, 5, 'Corporate Office: Clara Crest Watches Insurance Ltd., Head Office, Mumbai - 400 001', 0, 1, 'C');
        
        // Page number
        $this->Cell(0, 5, 'Page ' . $this->PageNo(), 0, 1, 'C');
    }
    
    // Function to create a bordered section
    function CreateSection($leftTitle, $leftWidth=80, $rightTitle='POLICY DETAILS', $rightWidth=110) {
        // Section headers
        $this->SetFont('Arial', 'B', 8);
        $this->Cell($leftWidth, 6, $leftTitle, 1, 0, 'L');
        $this->Cell($rightWidth, 6, $rightTitle, 1, 1, 'L');
        
        // Start Y position for content
        return $this->GetY();
    }
    
    // Function to add a row in the left column
    function LeftColumn($label, $value, $startY, $lineHeight=6, $width=80) {
        $this->SetY($startY);
        $this->SetX(10);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell($width, $lineHeight, $label, 'L', 2);
        
        $this->SetX(10);
        $this->SetFont('Arial', '', 8);
        $this->MultiCell($width, $lineHeight, $value, 'L');
        
        return $this->GetY();
    }
    
    // Function to add a row in the right column
    function RightColumn($label, $value, $startY, $lineHeight=6, $leftWidth=80, $rightWidth=110) {
        $this->SetY($startY);
        $this->SetX(10 + $leftWidth);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(40, $lineHeight, $label, 0, 0);
        
        $this->SetFont('Arial', '', 8);
        $this->Cell($rightWidth - 40, $lineHeight, $value, 0, 1);
        
        return $this->GetY();
    }
}

// Initialize PDF
$pdf = new InsurancePDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Policy holder and policy details section
$startY = $pdf->CreateSection('DETAILS OF THE POLICYHOLDER');

// Left column - Policy holder details
$currentY = $startY;
$currentY = $pdf->LeftColumn('Insured Name:', $insurance['fullname'], $currentY);
$currentY = $pdf->LeftColumn('Insured Address:', 
                           $insurance['address'] . ', ' . 
                           $insurance['city'] . ', ' . 
                           $insurance['state'] . ', ' . 
                           $insurance['pincode'], $currentY, 6);
$currentY = $pdf->LeftColumn('Contact No (s):', $insurance['phone'], $currentY);
$currentY = $pdf->LeftColumn('Email Address:', $insurance['email'], $currentY);

// Right column - Policy details
$currentY = $startY;
$currentY = $pdf->RightColumn('Policy Issuing Office:', 'Clara Crest Watches, Mumbai', $currentY);
$currentY = $pdf->RightColumn('Period of Insurance:', 
                            'From ' . $payment_date->format('d-M-Y') . ' to ' . 
                            $expiry_date->format('d-M-Y'), $currentY);
$currentY = $pdf->RightColumn('Policy Issued On:', $payment_date->format('d-M-Y'), $currentY);
$currentY = $pdf->RightColumn('Coverage No:', $policy_number, $currentY);
$currentY = $pdf->RightColumn('Transaction ID:', $insurance['transaction_id'], $currentY);

// Close the section
$maxY = max($currentY, $pdf->GetY());
$pdf->Line(10, $maxY, 200, $maxY);
$pdf->Ln(5);

// Watch details section
$pdf->CreateSection('WATCH DETAILS');
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(40, 8, 'Plan Type', 1, 0, 'C');
$pdf->Cell(150, 8, $insurance['plan_type'], 1, 1, 'C');

// Define coverage based on plan type
$coverageAmount = "0";
switch(trim($insurance['plan_type'])) {
    case 'BASIC PROTECTION':
        $coverageAmount = "₹7,500";
        break;
    case 'CLASSIC PROTECTION':
        $coverageAmount = "₹15,000";
        break;
    case 'PREMIUM COVERAGE':
        $coverageAmount = "₹25,000";
        break;
    case 'ULTIMATE ASSURANCE':
        $coverageAmount = "₹50,000";
        break;
}

// Coverage details as table
$pdf->Ln(5);
$pdf->CreateSection('SCHEDULE OF PREMIUM (INR ₹)', 100, 'LIABILITY (₹)', 90);

// Basic coverage details
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(100, 6, 'Basic Coverage Amount', 'LR', 0);
$pdf->Cell(90, 6, $coverageAmount, 'LR', 1);

$pdf->Cell(100, 6, 'Theft Protection', 'LR', 0);
$pdf->Cell(90, 6, 'Yes', 'LR', 1);

$pdf->Cell(100, 6, 'Damage Protection', 'LR', 0);
$pdf->Cell(90, 6, 'Yes', 'LR', 1);

$pdf->Cell(100, 6, 'Total Premium (including GST)', 'LR', 0);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(90, 6, '₹' . number_format($insurance['plan_amount'], 2), 'LR', 1);

// Close the coverage table
$pdf->Cell(190, 0, '', 'T', 1);
$pdf->Ln(5);

// Terms and conditions
$pdf->CreateSection('TERMS AND CONDITIONS', 190);
$pdf->SetFont('Arial', '', 7);

$terms = "1. This insurance policy is subject to the terms and conditions outlined in the policy document.\n".
"2. The coverage begins from the payment date and is valid until the expiry date mentioned above.\n".
"3. Any claims must be reported within 7 days of the incident.\n".
"4. The insurance covers damage, theft, and loss as per the plan details.\n".
"5. For claim processing, please contact our customer support with your policy number.\n".
"6. The policy is non-transferable and non-refundable.\n".
"7. The company reserves the right to investigate any claims before settlement.\n".
"8. The maximum liability is limited to the coverage amount specified in your plan.";

$pdf->MultiCell(190, 4, $terms, 'LR');
$pdf->Cell(190, 0, '', 'T', 1);
$pdf->Ln(10);

// Signature section
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(95, 6, 'Date: ' . date('d/m/Y'), 0, 0);
$pdf->Cell(95, 6, 'For Clara Crest Watches Insurance Ltd.', 0, 1, 'R');

$pdf->Ln(10);
$pdf->Cell(95, 6, '', 0, 0);
$pdf->Cell(95, 6, 'Authorized Signatory', 0, 1, 'R');

// Output PDF
$pdf->Output('D', 'Insurance_Policy_'.$policy_number.'.pdf');
?> 