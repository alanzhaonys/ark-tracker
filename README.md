# Purpose
[ARK ETF](https://ark-funds.com/) are very popular among investors, not only to invest but also to study what stocks ARK buys and sells on daily basis. ***After all, All ARK EFTs are actively managed and what could go wrong when there are smart people that have already done the homework for you.*** OK, this is a VERY BAD advise, please use your own judgement when investing.

ARK have been very transparent on their trades, [they publish daily transactions for you to download on their website](https://ark-funds.com/investor-resources). However, you don't want to sit around every day to download the files. If you miss a day, the file gets overwritten with the new one, so you don't want to miss it.

This is a set of tools that you can setup on your server to automatically grab the CSV files and then store every records in transactional database(MySQL) so you analyze with SQL and maybe do ML with it.

Proceed if you're:
 - **Interested in ARK ETFs**
 - **Familiar with SQL and will do something with the data**

This tool will only process ARKK, ARKQ, ARKG, ARKF and ARKW files. It does not care about PRNT and IZRL at the moment.

Make sure your (Linux) server have ample disk space for the growing data before installation.

# Database Setup and Configurations

***Hey, before we begin, your server must have MySQL installed, OK?!***

 1. Clone the repository
 2. Create a new database in your MySQL server, name it whatever you want
 3. Load `schema.sql` into the new database
 4. Copy `config.sample.php` to `config.php`
 5. Edit `config.php` and fill out the `db_*` values

# Scripts

`ark-invest.sh`

This is the **crawler** that downloads CSV files published daily by ARK from [https://ark-funds.com/investor-resources](https://ark-funds.com/investor-resources)

`ark-invest.php`

This is the ETL script that stores every transactions into database.  

# CRON Jobs

**ARK publishes the files usually around 7pm every weekday. Run the scripts after 8pm to be sure.**

1. Run `crontab -e` to edit your CRON jobs, copy and paste lines below.
2. Change `/root/Desktop/ark-invest` to your repo path.
```
# ark-invest.sh cron, runs the crawler every day at 8:30pm
30 20 * * * cd /root/Desktop/ark-invest && ./ark-invest.sh >> /var/log/ark-invest/ark-invest-sh.log 2>&1

# ark-invest.php cron, runs the ETL script every day at 9:00pm
0 21 * * * cd /root/Desktop/ark-invest && php ark-invest.php >> /var/log/ark-invest/ark-invest-php.log 2>&1
```

# What Now?
Wait for the data to grow and do something cool with it, like creating charts. Do share with me!

