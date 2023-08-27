<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

class PrintTestCommand extends Command
{
    protected $signature = 'print:test';

    protected $description = 'Testuje przykładowy bod do testu';

    public function handle()
    {

        /* Fill in your own connector here */

        $connector = new FilePrintConnector("php://stdout");
        //$connector = new NetworkPrintConnector("10.x.x.x", 9100);

        //$connector = new WindowsPrintConnector("Receipt Printer Name");
        //$connector = new WindowsPrintConnector("smb://computername/Receipt Printer");
        //$connector = new WindowsPrintConnector("smb://Guest@computername/Receipt Printer");
        //$connector = new WindowsPrintConnector("smb://FooUser:secret@computername/workgroup/Receipt Printer");
        //$connector = new WindowsPrintConnector("smb://User:secret@computername/Receipt Printer");
        //$connector = new WindowsPrintConnector("LPT1");





        /* Information for the receipt */

        $items = array(
            new item("Example item #1", "1.00"),
            new item("Another thing", "2.55"),
            new item("Something else", "1.00"),
            new item("A final item", "3.45"),
        );
        $sSubTotal = 8.00;

        for ($i = 0; $i < 10; $i++) {

            $price = (string) rand(2,9).rand(2,9).'.'.rand(2,9).rand(2,9);
            $sSubTotal += floatval($price);
            $items[] = new item(Str::random(rand(12,20)), $price);
        }
        $sTotal =  round($sSubTotal * 1.23,2);
        $sTax =  round( $sTotal - $sSubTotal,2);
        $sSubTotal =  round($sSubTotal,2);

        $subtotal = new item('NETTO', $sSubTotal);
        $tax = new item('PODATEK', $sTax);
        $total = new item('BRUTTO', $sTotal);

        /* Date is kept the same for testing */
        // $date = date('l jS \of F Y h:i:s A');
        // $date = "Monday 6th of April 2015 02:56:25 PM";

        $date = now()->dayName.' '.now()->format('d').' '.now()->shortMonthName.' '.now()->format('Y');

        $filePath = base_path('docs/resources/escpos-php.png');
        $logo = EscposImage::load($filePath, false);


        /* Start the printer */

        $printer = new Printer($connector);

//        /* Print top logo */
//        $printer->setJustification(Printer::JUSTIFY_CENTER);
//        $printer->graphics($logo);

        /* Name of restaurant */
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer->text("Restauracja Superasta.\n");
        $printer->selectPrintMode();
        $printer->text("Punkt No. 42.\n");
        $printer->feed();

        /* Title of receipt */
        $printer->setEmphasis(true);
        $printer->setFont(Printer::FONT_C);
        $printer->text("BON WYDRUK 123\n");
        $printer->setEmphasis(false);

        /* Items */
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->setEmphasis(true); // do podkreślenie
        $printer->setFont(Printer::FONT_B);
        $printer->text(new item('', 'pln'));

        $printer->setEmphasis(false);
        $printer->setFont(Printer::FONT_B);
        foreach ($items as $item) {
            $printer->text($item);
        }
        $printer->setEmphasis(true);
        $printer->text($subtotal);
        $printer->setEmphasis(false);
        $printer->feed();

        /* Tax and total */
        $printer->text($tax);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer->text($total);
        $printer->selectPrintMode();

        /* Footer */
        $printer->feed(2);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Super thx za wizyte\n");
        $printer->text("Zamowienia online  www.example.com\n");
        $printer->feed(2);
        $printer->text($date . "\n");

        /* Cut the receipt and open the cash drawer */
        $printer->cut();
        $printer->pulse();

        $printer->close();


    }
}


class item
{
    private $name;
    private $price;

    public function __construct($name = '', $price = '' )
    {
        $this->name = $name;
        $this->price = $price;
    }

    public function __toString()
    {
        $rightCols = 10;
        $leftCols = 38;

        $left = str_pad($this->name, $leftCols);

        $right = str_pad( $this->price, $rightCols, ' ', STR_PAD_LEFT);
        return "$left$right\n";
    }
}

