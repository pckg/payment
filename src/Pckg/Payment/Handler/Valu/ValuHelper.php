<?php namespace Pckg\Payment\Handler\Valu;

class ValuHelper
{


    // Functions_ResponseExpires()
    // Functions_UploadFile_GetContent($sFileName)
    // Functions_DownloadFile_PutFile($sFileName, $sFileExtension, $sFileData)
    // Functions_Request($sParameterName) 
    // Functions_RequestNumber($sParameterName, $nMin, $nMax, $nDefault)
    // Functions_RequestFloat($sParameterName, $fltMin, $fltMax, $fltDefault)
    // Functions_RequestString($sParameterName, $nMaxLen)
    // Functions_Validate_String($sData)
    // Functions_CInt($sNum)
    // Functions_CDbl($sNum)
    // Functions_ParseCityHouse($sAddress, &$sCity, &$sHouse)
    // Functions_GetParameterValue($sData, $sParameterName)

    //#######################################################################
    //##  Functions_ResponseExpires()
    //####################################################################### 
    public static function Functions_ResponseExpires()
    {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-cache"); // HTTP/1.1
        header("Pragma: no-cache"); // HTTP/1.0
        return 0;
    }

    //#######################################################################
    //##  Functions_UploadFile_GetContent($sFileName)
    //#######################################################################
    public static function Functions_UploadFile_GetContent($sFileName)
    {
        $data = "";
        $imgfile = $_FILES[$sFileName]['tmp_name'];
        $imgfile_type = $_FILES[$sFileName]['type'];
        $imgfile_name = $_FILES[$sFileName]['name'];
        if (is_uploaded_file($imgfile)) {
            $file = fopen($imgfile, 'rb');
            $data = fread($file, filesize($imgfile));
            fclose($file);
        }
        return $data;
    }

    //#######################################################################
    //##  Functions_DownloadFile_PutFile($sFileName, $sFileData)
    //#######################################################################
    public static function Functions_DownloadFile_PutFile($sFileName, $sFileExtension, $sFileData)
    {
        $iFileLen = strlen($sFileData);
        $datum = date("D, d M Y H:i:s") . " GMT";
        header("Content-Type: application/$sFileExtension");
        header("Accept-Ranges: bytes");
        header("Last-Modified: $datum");
        header("Expires: $datum");
        header("Content-Disposition: inline; filename=$sFileName.$sFileExtension");
        header("Pragma: no-cache");
        //header("Cache-Control: no-cache, must-revalidate");
        header("Cache-control: private");
        header("Content-Length: $iFileLen\n");
        echo "$sFileData";
    }

    //##############################################################
    //## Functions_GetServerVariable($sVARIABLE)
    //##############################################################
    public static function Functions_GetServerVariable($sVARIABLE)
    {
        $sReturn = "";
        if (isset($_SERVER[$sVARIABLE])) {
            $sReturn = $_SERVER[$sVARIABLE];
        }
        return $sReturn;
    }

    //#######################################################################
    //##  Functions_Request($sParameterName)
    //#######################################################################
    public static function Functions_Request($sParameterName)
    {
        $sReturn = "";
        if (isset($_POST[$sParameterName])) {
            $sReturn = $_POST[$sParameterName];
        } elseif (isset($_GET[$sParameterName])) {
            $sReturn = $_GET[$sParameterName];
        }
        return stripslashes($sReturn);
    }

    //#######################################################################
    //##  Functions_RequestNumber($sParameterName, $nMin, $nMax, $nDefault)
    //#######################################################################
    public static function Functions_RequestNumber($sParameterName, $nMin, $nMax, $nDefault)
    {
        $nNum = ValuHelper::Functions_Request($sParameterName);
        if ($nNum == "") {
            $nNum = "" . $nDefault;
        }
        $nReturn = intval($nNum);
        if (!(($nReturn >= $nMin) && ($nReturn <= $nMax))) {
            $nReturn = $nDefault;
        }
        return $nReturn;
    }

    //#######################################################################
    //##  Functions_RequestFloat($sParameterName, $fltMin, $fltMax, $fltDefault)
    //#######################################################################
    public static function Functions_RequestFloat($sParameterName, $fltMin, $fltMax, $fltDefault)
    {
        $fltNum = ValuHelper::Functions_Request($sParameterName);
        $fltReturn = floatval(str_replace(",", ".", "" . $fltNum));
        if (!(($fltReturn >= $fltMin) && ($fltReturn <= $fltMax))) {
            $fltReturn = $fltDefault;
        }
        return $fltReturn;
    }

    //#######################################################################
    //##  Functions_RequestString($sParameterName, $nMaxLen)
    //#######################################################################
    public static function Functions_RequestString($sParameterName, $nMaxLen)
    {
        $sReturn = ValuHelper::Functions_Request($sParameterName);
        if (strlen($sReturn) > $nMaxLen) {
            $sReturn = substr($sParameterName, 0, $nMaxLen);
        }
        return ValuHelper::Functions_Validate_String($sReturn);
    }

    //#######################################################################
    //##  Functions_Validate_String($sData)
    //#######################################################################  
    public static function Functions_Validate_String($sData)
    {
        $sValidChars = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 1, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
            1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
            1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
            1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
            1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
            1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0,
            1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0,
            1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
        $iLen = strlen($sData);
        for ($i = 0; $i < $iLen; $i++) {
            if ($sValidChars[ord($sData[$i])] == 0) {
                $ta = $sData[$i];
                $sData[$i] = chr(32);
            }
        }
        $sData = str_replace("'", "''", $sData);
        $sData = str_replace("%", " ", $sData);
        $sData = str_replace("\\", " ", $sData);
        return $sData;
    }

    //#######################################################################
    //##  Functions_CInt($sNum)
    //#######################################################################   
    public static function Functions_CInt($sNum)
    {
        return intval($sNum);
    }

    //#######################################################################
    //##  Functions_CDbl($sNum)
    //#######################################################################  
    public static function Functions_CDbl($sNum)
    {
        return floatval(str_replace(",", ".", "" . $sNum));
    }

    //#######################################################################
    //##  Functions_ParseCityHouse($sAddress, &$sCity, &$sHouse)
    //#######################################################################  
    public static function Functions_ParseCityHouse($sAddress, &$sCity, &$sHouse)
    {
        $sData = "" . $sAddress;
        $iLen = strlen($sData);
        $sHouse = "";
        $sCity = "";

        $a = 0;
        if ($iLen > 0) {
            for ($i = $iLen - 1; $i > -1; $i--) {
                if ($a == 0) {
                    if ($sData[$i] == " ") {
                        $a = 1;
                    } else {
                        $sHouse = $sData[$i] . $sHouse;
                    }
                } else {
                    $sCity = $sData[$i] . $sCity;
                }
            }
        }
        return 0;
    }

    //#######################################################################
    //##  Functions_GetParameterValue($sData, $sParameterName)
    //#######################################################################  
    public static function Functions_GetParameterValue($sData, $sParameterName)
    {
        $sReturn = "";
        $nFrom = strpos($sData, $sParameterName . "=");

        if (!($nFrom === false)) {
            $nFrom = $nFrom + strlen($sParameterName) + 1;
            $nTo = strpos($sData, "#", $nFrom);
            if ($nTo === false) {
                $nTo = strlen($sData);
            }
            if ($nTo > $nFrom) {
                $sReturn = substr($sData, $nFrom, $nTo - $nFrom);
            }
        }

        return $sReturn;
    }

    //################################################################
    //# MakeOrderHead  D: Order head
    //################################################################
    public static function MakeOrderHead($sTaxNumber, $sFirstName, $sLastName, $sCompanyName, $sStreet, $sHouse, $sPostCode, $sCity, $sCountry, $sTelephone, $sEmail, $sPrice)
    {
        $sXML = "";
        $EOL = "\r\n";
        $Date = "" . gmdate("d.m.Y");
        $sCurrency = "EUR";

        $sXML = "<meta name=\"Order\" content=\"" . $EOL .
            "<ORDER>" . $EOL .
            "<ORDER_HEAD>" . $EOL .
            "  <CustomerTaxNumber>" . $sTaxNumber . "</CustomerTaxNumber>" . $EOL .
            "  <CustomerFirstName>" . $sFirstName . "</CustomerFirstName>" . $EOL .
            "  <CustomerMiddleName></CustomerMiddleName>" . $EOL .
            "  <CustomerLastName>" . $sLastName . "</CustomerLastName>" . $EOL .
            "  <CustomerNameSuffix></CustomerNameSuffix>" . $EOL .
            "  <CustomerCompanyName>" . $sCompanyName . "</CustomerCompanyName>" . $EOL .
            "  <CustomerStreet>" . $sStreet . "</CustomerStreet>" . $EOL .
            "  <CustomerHouse>" . $sHouse . "</CustomerHouse>" . $EOL .
            "  <CustomerPostCode>" . $sPostCode . "</CustomerPostCode>" . $EOL .
            "  <CustomerCity>" . $sCity . "</CustomerCity>" . $EOL .
            "  <CustomerState></CustomerState>" . $EOL .
            "  <CustomerCountry>" . $sCountry . "</CustomerCountry>" . $EOL .
            "  <CustomerTelephone>" . $sTelephone . "</CustomerTelephone>" . $EOL .
            "  <CustomerFax></CustomerFax>" . $EOL .
            "  <CustomerEMail>" . $sEmail . "</CustomerEMail>" . $EOL .
            "  <DeliveryFirstName>" . $sFirstName . "</DeliveryFirstName>" . $EOL .
            "  <DeliveryMiddleName></DeliveryMiddleName>" . $EOL .
            "  <DeliveryLastName>" . $sLastName . "</DeliveryLastName>" . $EOL .
            "  <DeliveryNameSuffix></DeliveryNameSuffix>" . $EOL .
            "  <DeliveryCompanyName>" . $sCompanyName . "</DeliveryCompanyName>" . $EOL .
            "  <DeliveryStreet>" . $sStreet . "</DeliveryStreet>" . $EOL .
            "  <DeliveryHouse>" . $sHouse . "</DeliveryHouse>" . $EOL .
            "  <DeliveryPostCode>" . $sPostCode . "</DeliveryPostCode>" . $EOL .
            "  <DeliveryCity>" . $sCity . "</DeliveryCity>" . $EOL .
            "  <DeliveryState></DeliveryState>" . $EOL .
            "  <DeliveryCountry>" . $sCountry . "</DeliveryCountry>" . $EOL .
            "  <DeliveryEMail>" . $sEmail . "</DeliveryEMail>" . $EOL .
            "  <DeliveryTelephone>" . $sTelephone . "</DeliveryTelephone>" . $EOL .
            "  <DeliveryFax></DeliveryFax>" . $EOL .
            "  <Currency>" . $sCurrency . "</Currency>" . $EOL .
            "  <Price>" . $sPrice . "</Price>" . $EOL .
            "  <DeliveryPrice>0</DeliveryPrice>" . $EOL .
            "  <DateFrom>" . $Date . "</DateFrom>" . $EOL .
            "  <DateTo>" . $Date . "</DateTo>" . $EOL .
            "  <OrderNumberInternal>3dc0</OrderNumberInternal>" . $EOL .
            "  <OrderCreated>" . $Date . "</OrderCreated>" . $EOL .
            "  <NotificationDate>" . $Date . "</NotificationDate>" . $EOL .
            "  <DeliveryType>OWN</DeliveryType>" . $EOL .
            "</ORDER_HEAD>" . $EOL .
            "<ORDER_LINE>";
        return $sXML;
    }

    //################################################################
    //# MakeOrderLine  D: add order line
    //################################################################
    public static function MakeOrderLine($sDescription, $sPrice, $sTaxRate, $sQuantity, $sUnit, $sArticleNumber)
    {
        $sXML = "";
        $EOL = "\r\n";

        $nPriceNoTax = round((ValuHelper::Functions_CDbl($sPrice) * 100) / (100 + ValuHelper::Functions_CDbl($sTaxRate)), 2);
        $nPriceSum = round((ValuHelper::Functions_CDbl($sPrice) * ValuHelper::Functions_CInt($sQuantity)), 2);
        $nPriceSumNoTax = round((ValuHelper::Functions_CDbl($nPriceSum) * 100) / (100 + ValuHelper::Functions_CDbl($sTaxRate)), 2);
        $nPriceTax = round($nPriceSum - $nPriceSumNoTax, 2);

        $sXML = "<PRODUCT>" . $EOL .
            "   <PageDescription>" . $sDescription . "</PageDescription>" . $EOL .
            "   <Price>" . $sPrice . "</Price>" . $EOL .
            "   <PriceNoTax>" . $nPriceNoTax . "</PriceNoTax>" . $EOL .
            "   <TaxRate>" . $sTaxRate . "</TaxRate>" . $EOL .
            "   <Quantity>" . $sQuantity . "</Quantity>" . $EOL .
            "   <PriceSum>" . $nPriceSum . "</PriceSum>" . $EOL .
            "   <PriceSumNoTax>" . $nPriceSumNoTax . "</PriceSumNoTax>" . $EOL .
            "   <PriceTax>" . $nPriceTax . "</PriceTax>" . $EOL .
            "   <Unit>" . $sUnit . "</Unit>" . $EOL .
            "   <ArticleNumber>" . $sArticleNumber . "</ArticleNumber>" . $EOL .
            " </PRODUCT>";
        return $sXML;
    }

    //################################################################
    //# MakeOrderEnd  D: Close XML order
    //################################################################
    public static function MakeOrderEnd()
    {
        $sXML = "";
        $EOL = "\r\n";
        $sXML = "  </ORDER_LINE>" . $EOL . "</ORDER>\">";
        return $sXML;
    }

}