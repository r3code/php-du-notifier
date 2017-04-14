<?php      

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
            
// Per Site disk usage information
class SiteDiskUsageData {
  private $domainName;
  private $absolutePath;
  private $bytesUsed;   
  
  public function __construct($domainName, $absolutePath) {
    $this->domainName = $domainName;
    $this->absolutePath = $absolutePath; 
    $this->bytesUsed = $this->GetDirSizeBytes($absolutePath);
  }               

	private function GetDirSizeBytes($absolutePath)
	{         
		$res = exec("du -b -s $absolutePath");  
    
		if (preg_match( "/\d+/", $res, $bytes) ) {
      return $bytes[0];
    }   
    return -1;
	}    
  
  public function DomainName() {
    return $this->domainName;
  }     
  
  public function AbsolutePath() {
    return $this->absolutePath;
  }     
  
  public function BytesUsed() {
    return $this->bytesUsed;
  }       
}   




// @returns array of SiteDiskUsageData objects
class SitesDiskUsageData {
  private $sitesInfo = array();  // ['domain' => .., 'path' => ..]  
  private $rootDir;
  
  public function __construct($sitesInfo, $rootDir) {
    $this->sitesInfo = $sitesInfo;  
    $this->rootDir = $rootDir;  
  }                               
  
  public function Calculate() {  
    $calculatedData = array(); 
    foreach ($this->sitesInfo as $info ) { 
      $absolutePath = $this->rootDir . $info['path'];
      $usageData = new SiteDiskUsageData($info['domain'], $absolutePath);
		  $calculatedData[] = $usageData; // array of objects SiteDiskUsageData 	
		} 
    return $calculatedData; 
  }
}






interface iReport
{               
  public function ContentType();
  public function Render();
}
  

class PlainTextDiskUsageReport implements iReport {  
  protected $diskUsageData;  
  protected $reportName;       
  protected $oneSiteSpaceQuotaBytes;
  protected $totalDiskQuotaBytes;  

  public function __construct($reportName, $diskUsageData, 
    $oneSiteSpaceQuotaBytes, $totalDiskQuotaBytes) {
    $this->diskUsageData = $diskUsageData;
    $this->reportName = $reportName;
    $this->oneSiteSpaceQuotaBytes = $oneSiteSpaceQuotaBytes;
    $this->totalDiskQuotaBytes = $totalDiskQuotaBytes; 
  }       
  
  public function ContentType() {
    return 'text/plain';  
  }
      
  protected function MakeHumanReadableSize($sizeBytes, $unit = 'auto')
  {         
    $sizeString = '';
    if ($unit == 'auto') {
      $sizeString = (
          ($sizeString >= (1024 * 1024 * 1024 * 1024))
            ? round($size_kbyt / (1024 * 1024 * 1024 * 1024), 2)." TB"
            : (
              ($sizeBytes >= (1024 * 1024 * 1024))
                ? round($sizeBytes / (1024 * 1024 * 1024), 2)." GB"
                : (
                  ($sizeBytes >= (1024 * 1024))
                  ? round($sizeBytes / (1024 * 1024), 2)." MB"
                  : round($sizeBytes / 1024, 2)."KB"
                )
            )
        );
    }
    else {
      switch (strtoupper($unit)) {
        case 'TB':
          $sizeString = round($sizeBytes / (1024 * 1024 * 1024 * 1024), 2)." TB"; 
          break;
        case 'GB':
          $sizeString = round($sizeBytes / (1024 * 1024 * 1024), 2)." GB";
          break;
        case 'MB':
          $sizeString = round($sizeBytes / (1024 * 1024), 2)." MB";
          break;
        case 'KB':
          $sizeString = round($sizeBytes / 1024, 2)." KB";
          break;
        default:
          $sizeString = $sizeBytes ." BYTES";
          break;
      }
    }
    return $sizeString;
  } 
                  

  protected function CalcFreeSizeBytes($space_used_bt, $space_limit_bt)
  {
    return $space_limit_bt - $space_used_bt;
  }  
     
  public function Render()
  {
    $sitesUsageData = $this->diskUsageData;
		$text = sprintf("= Disk Usage Report =\r\n");
		$text .=  sprintf("Date: %s\r\n", date("m.d.Y H:m:s"));
		$text .=  "Format: text/plain\r\n";  
    $totalSpaceStr = $this->MakeHumanReadableSize($this->totalDiskQuotaBytes);
		$text .=  "Total available space: $totalSpaceStr\r\n";
		$text .=  sprintf("%5s %-20s %-10s %-10s %-10s\r\n",'№','SITE','USED', 
      'FREE', 'USED %');   
      
		$num = 1;     
		foreach ($sitesUsageData as $siteUsageData) {   
      $domain = $siteUsageData->DomainName();              
      
      $usedSpaceSize = $this->MakeHumanReadableSize($siteUsageData->BytesUsed(),'MB');
      $freeSpaceSize = $this->MakeHumanReadableSize($this->CalcFreeSizeBytes(
        $siteUsageData->BytesUsed(), $this->oneSiteSpaceQuotaBytes),'MB');
      $spaceUsedPercents = round(100 * 
        $siteUsageData->BytesUsed()/$this->oneSiteSpaceQuotaBytes, 2); 
			$text .= sprintf("%02d) %-20s %-10s %-10s %.2f\r\n",
				$num, $domain, $usedSpaceSize, $freeSpaceSize, $spaceUsedPercents);
			$num++;
		}
    return $text;
  }
}


class HtmlDiskUsageReport extends PlainTextDiskUsageReport implements iReport {   
  public function ContentType() {
    return 'text/html';  
  }
       
  public function Render()
  {    
    $sitesUsageData = $this->diskUsageData;  
    
		$title = 'Disk Usage Report';    
    $reportDate = date("d.m.Y H:m:s");
    $rows = '';     
    $num = 1; 
    foreach ($sitesUsageData as $siteUsageData) {   
      $domain = $siteUsageData->DomainName();              
      
      $usedSpaceSize = $this->MakeHumanReadableSize($siteUsageData->BytesUsed(),'MB');
      $freeSpaceSize = $this->MakeHumanReadableSize($this->CalcFreeSizeBytes(
        $siteUsageData->BytesUsed(), $this->oneSiteSpaceQuotaBytes),'MB');
      $spaceUsedPercents = round(100 * 
        $siteUsageData->BytesUsed()/$this->oneSiteSpaceQuotaBytes, 2); 
			
      $used_space_width = round(100 * 
        ($siteUsageData->BytesUsed()/$this->oneSiteSpaceQuotaBytes));
			$free_space_width = 100 - $used_space_width;
      
      $spaceQuota = $this->MakeHumanReadableSize($this->oneSiteSpaceQuotaBytes);
      
      $rows .= "<tr>"
			."<td>$num</td>"
			."<td>$domain</td>"
			."<td>$spaceQuota</td>"
			.'<td>'
				. '<div class="space-used" style="width:'.$used_space_width.'%">'
				. $usedSpaceSize . '</div>'
			."</td>"
			."<td class='usedPercents'>" . $spaceUsedPercents . "</td>"
			."<tr>";
      
			$num++;
		}    
		
		$totalSpaceStr = $this->MakeHumanReadableSize($this->totalDiskQuotaBytes);
		
		$table = <<<TABLE
<table>
  <tr>
    <th>#</th>
    <th>Site</th>
    <th>Quota</th>
    <th width="500">Used / Free</th>
    <th>Used, %</th>
  <tr>
  $rows
</table>   

TABLE;
		
		$body = <<<BODY
<h1>$title</h1>
<p><b>Date: $reportDate</b></p>
<p>Format: text/html</p>
<p>Total available space: $totalSpaceStr</p>
$table	

BODY;
		
		$html = <<<HTML
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru-RU" lang="ru-RU">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<head><title>$title</title>
<style>
body {font-family: Arial}  
table {
  border-spacing: 0;
  border-collapse: collapse;
}
table, td {border: 1px solid #eee}
th {text-align: center; font-weight: bold}
td { padding: 5px 7px; }
.space-used {background-color: red; color: #333;}
.space-free {background-color: #009900; color: #a0cda8;}
</style>
</head>
<body>
$body
</body>
</html>

HTML;
		
      return $html;
  }
}




class Letter {  
  protected $mailFrom; 
  protected $mailTo; 
  protected $subject; 
  protected $contentType;   
  protected $content;   
  
  function __construct($mailFrom, $mailTo, $subject, $contentType, $content) {
    $this->mailFrom = $mailFrom;
    $this->mailTo = $mailTo;
    $this->subject = "=?utf-8?B?".base64_encode($subject)."?=";
    $this->contentType = $contentType;
    $this->content = stripslashes($content); 
  } 
  
  public function MailFrom() {
    return $this->mailFrom;
  }
  public function MailTo() {
    return $this->mailTo;
  }
  public function Subject() {
    return $this->subject;
  }
  public function ContentType() {
    return $this->contentType;
  }
  public function Content() {
    return $this->content;
  }    
}  
  

         


class Postman 
{   
  function Send($letter) {
    $sent = mail($letter->MailTo(), $letter->Subject(),
      $letter->Content(), 
			"MIME-Version: 1.0\r\n"
			."Content-Type: " . $letter->ContentType() . "; charset=UTF-8\r\n"
			."From: " . $letter->MailFrom() . "\r\n"
			."Reply-To: " . $letter->MailFrom() . "\r\n"
			."X-Mailer: PHP/" . phpversion()		
    );      
    if(!$sent) {
      $error = print_r(error_get_last(), true);
      echo("Mail send error: $error\r\n");
    }
    return $sent;   
  }    
}





function main() 
{
	$parameters = array(
	  'm:',
	);
	$options = getopt(implode('', $parameters)); // php 5.2 doesn't support longparams
                               
  $json = file_get_contents( realpath(dirname(__FILE__)) . "/config.json");
  $config = json_decode($json, true);   
  
	$usageData = new SitesDiskUsageData($config['sites'], $config['rootDir']);
  $calculatedUsage = $usageData->Calculate();       
    
  $title = $config['reportInfo']['name']; 
  $totalSpaceBytes = $config['totalDiskQuotaMb'] * 1024 * 1024; 
  $oneSiteSpaceQuotaBytes = floor($totalSpaceBytes / $config['maxSiteCount']); 
  if( $config['reportInfo']['type'] == 'html' ) {
    $report = new HtmlDiskUsageReport($title, $calculatedUsage, 
      $oneSiteSpaceQuotaBytes,  $totalSpaceBytes);
  } else {
    $report = new PlainTextDiskUsageReport($title, $calculatedUsage, 
      $oneSiteSpaceQuotaBytes, $totalSpaceBytes);
  }   
  
  $content = $report->Render();
	
	// Send report to mail and pass to output
  $isMailtoParamSet = is_array($options) && isset($options['m'])
	if ( $isMailtoParamSet ) 
	{          
    $emailAddrFromCmdParam = $options['m']
		$mailTo = isset($options['m']) 
			? $emailAddrFromCmdParam
			: null;     
      
		if (isset($mailTo)) {
			echo "Sending report to $mailTo...\r\n";     
      
      $mailFrom = $config['reportInfo']['mailFrom'];
      
      $letter = new Letter($mailFrom, $mailTo, $title, $report->ContentType(), 
        $content);      
      
      $postman = new Postman();
      $result = $postman->Send($letter); 
			echo $result ? 'OK' : 'Fail';
      echo "\r\n";
			$letter = null;
			$postman = null;
			exit($result);
		}
		else {
			exit('Error "-m" Mailto param value is not set.');
		}
    } 
	else {		
		echo $content;
	}	
  $usageData = null; 
}

main();

?>
