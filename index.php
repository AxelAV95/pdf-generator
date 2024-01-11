<?php

require_once('tcpdf/tcpdf.php');

class PDF extends TCPDF
{
        public function Header()
        {
                // Header content here
                $this->SetFont('Helvetica', 'B', 12);

                $this->Cell(0, 10, 'Your Estate PDF Report', 0, 1, 'C');
                $this->Ln(10);
        }

        public function Footer()
        {
                // Footer content here
                $this->SetY(-15);
                $this->SetFont('Helvetica', 'I', 8);
                $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
        }

        public function generatePDF($estateID)
        {
                // Fetch property details

                //-----------------------------------------------------------------
                //Alternatively all this data can be fetched using a data controller.
                //This logic could be used there and called here using the controller class 
                
                $propertyQuery = "SELECT Properties.LotNumber, Properties.SizeSqMeters, Properties.Frontage, Properties.Depth, Properties.PriceInDollars, Properties.AvailabilityStatus, StreetNames.StreetName, Stages.StageName,
        ResidentialCodes.Code AS ResidentialCode, FireCodes.Code AS FireCode
        FROM Properties
        JOIN StreetNames ON Properties.StreetNameID = StreetNames.StreetNameID JOIN Stages ON Properties.StageID = Stages.StageID
        JOIN ResidentialCodes ON Properties.ResidentialCode = ResidentialCodes.Code JOIN FireCodes ON Properties.FireCode = FireCodes.Code
        WHERE Properties.EstateID = ?
        ORDER BY Properties.StageID, Properties.LotNumber";

                $conn = new mysqli("localhost", "root", "", "pdftest");
                $propertyStmt = $conn->prepare($propertyQuery);
                $propertyStmt->bind_param("i", $estateID);
                $propertyStmt->execute();
                $propertyResult = $propertyStmt->get_result();
                $properties = $propertyResult->fetch_all(MYSQLI_ASSOC);

                 // Fetch state details
                $estateQuery = "SELECT * FROM Estates WHERE EstateID = ?";
                $estateStmt = $conn->prepare($estateQuery);
                $estateStmt->bind_param("i", $estateID);
                $estateStmt->execute();
                $estateResult = $estateStmt->get_result();
                $estateData = $estateResult->fetch_assoc();

                // Create PDF
                $pdf = new PDF();
                $pdf->AddPage();

                // Logo
                $logoBlob = $estateData['Logo']; // Assuming the logo is stored as a blob
                $pdf->Image('@' . $logoBlob, 50, 10, 100, 50, 'PNG');
               

                // Location
                $pdf->SetY(70);
                $pdf->SetX(0);
                $pdf->Cell(0, 10, $properties[0]['StreetName'], 0, 1, 'C');

                // Table headers
                $pdf->SetFillColor(0, 128, 0); // Green background
                $pdf->SetTextColor(255);
                $pdf->SetDrawColor(255, 255, 255);
                $pdf->SetFont('Helvetica', 'B', 12);

                $pdf->Cell(20, 10, 'Lot', 1, 0, 'C', 1);
                $pdf->Cell(30, 10, 'Street Name', 1, 0, 'C', 1);
                $pdf->Cell(20, 10, 'SQM', 1, 0, 'C', 1);
                $pdf->Cell(20, 10, 'Frontage', 1, 0, 'C', 1);
                $pdf->Cell(20, 10, 'Depth', 1, 0, 'C', 1);
                $pdf->Cell(20, 10, 'RCode', 1, 0, 'C', 1);
                $pdf->Cell(20, 10, 'BAL', 1, 0, 'C', 1);
                $pdf->Cell(20, 10, 'Price', 1, 0, 'C', 1);
                $pdf->Cell(20, 10, 'Status', 1, 1, 'C', 1);

                // Loop through properties and display data
                $previousStage = null;

                $pdf->SetLeftMargin(10);
                foreach ($properties as $property) {
                        if ($property['StageName'] !== $previousStage) {
                                $pdf->SetFillColor(0, 128, 0); // Green background
                                $pdf->SetTextColor(255);
                                $pdf->SetFont('Helvetica', 'B', 12);
                                $pdf->Cell(0, 10, $property['StageName'], 1, 1, 'C', 1);
                                $previousStage = $property['StageName'];
                        }

                        $pdf->SetFont('Helvetica', '', 10);
                        $pdf->SetTextColor(255, 0, 0); // Red text
                        $pdf->Cell(20, 10, $property['LotNumber'], 1, 0, 'C');
                        $pdf->Cell(30, 10, $property['StreetName'], 1, 0, 'C');
                        $pdf->Cell(20, 10, $property['SizeSqMeters'], 1, 0, 'C');
                        $pdf->Cell(20, 10, $property['Frontage'], 1, 0, 'C');
                        $pdf->Cell(20, 10, $property['Depth'], 1, 0, 'C');
                        $pdf->Cell(20, 10, $property['ResidentialCode'], 1, 0, 'C');
                        $pdf->Cell(20, 10, $property['FireCode'], 1, 0, 'C');
                        $pdf->Cell(20, 10, $property['PriceInDollars'], 1, 0, 'C');
                        $pdf->Cell(20, 10, $property['AvailabilityStatus'], 1, 1, 'C');
                }


                // Description
                $pdf->SetY(200);
                $pdf->SetX(0);
                $pdf->SetTextColor(255, 0, 0); // Red text
                $pdf->Cell(0, 10, "Print the Description Field Here", 0, 1, 'C');

                // Description1
                $pdf->SetY(210);
                $pdf->SetX(0);
                $pdf->SetTextColor(0); // Black text
                $pdf->Cell(0, 10, "Print the Description1 Field Here", 0, 1, 'C');

                // Disclaimer
                $pdf->SetY(220);
                $pdf->SetX(0);
                $pdf->SetTextColor(255, 0, 0); // Red text
                $pdf->Cell(0, 10, "Disclaimer Text Here", 0, 1, 'C');

                // Agent logo
                $agentLogoBlob = $estateData['AgentLogo'];
                $imageWidth = 150; // Assuming the width of the image is 150 units
                $pageWidth = $pdf->GetPageWidth();

                // Calculate the x-coordinate to center the image
                $xCoordinate = ($pageWidth - $imageWidth) / 2;

                $pdf->Image('@' . $agentLogoBlob, $xCoordinate, $pdf->GetPageHeight() - 60, $imageWidth, 30, 'PNG');

                // Output PDF
                $pdf->Output();
        }
}

// Here you can receive the estateID from client and generate the pdf 
$estateID = 2;
$pdfGenerator = new PDF();
$pdfGenerator->generatePDF($estateID);

/*
Alternatively, sending id by url:
pdf/index.php?id=1
if(isset($_GET['id'])){
        $estateID = $_GET['id'];
 $pdfGenerator = new PDF();
 $pdfGenerator->generatePDF($estateID);

} 

*/ 
