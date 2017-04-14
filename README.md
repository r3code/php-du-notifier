# php-du-notifier
PHP script to notify an admin by email of how much space allocated by web sites on shared Linux hosting.

## Install

Download and copy files to your shared hosting. 

## Config
Edit `config.json` before use.  
Config fields are:

```
rootDir - absoulte path to your account on web-hosting,  
totalDiskQuotaMb - total accoun space limit in megabytes,  
maxSiteCount - sites count used to calculate per site space limit, typycally allowed by tariff,  
reportInfo - contains report config,  
    mailFrom - e-mail addres to use as from header (e.g. status@example.org),       
    name - title for the report,       
    type - (html|plain) type of generated report,       
sites - (array) of site info, with structure:    
    domain - domain name,       
    path - path to site dir from `rootDir`
```     
    

### Mail report every moth
Allow to execute `dunotifier.php`, e.g. `chmod +x dunotifier.php` to allow run from cron.
Configure cron task to run `/path-to-scripts/dunotifier.php -m mailme@example.org` for example once a month.
It will send you a letter with a report once a month.

### Instant report
Open your web site at http://site.com/path-to-scripts/dunotifier.php to see the html report.
Set password for this folder to protect it from undesired access.

## Email Report Example

The letter in Gmail interface.

![DuNotifier E-mail report](du-notifier-email-report.png)

## Online HTML Report Example

See below.

![DuNotifier HTML report](du-notifier-html-report.png)

