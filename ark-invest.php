<?php

include_once 'config.php';
include_once 'functions.php';

date_default_timezone_set('America/New_York');

$db_conn = new mysqli($db_host, $db_username, $db_password, $db_name, $db_port);
if ($db_conn->connect_error) {
  die('Connection failed: ' . $db_conn->connect_error);
}

if ($argc == 2) {

  $for_date = $argv[1];

  run($for_date);

} else {

  $today = date('Y-m-d');
  $day_of_week = date('l');

  # Skip for weekend
  if (in_array($day_of_week, ['Saturday', 'Sunday'])) {
    echo "No run for weekend\n";
    exit;
  }

  run($today);
}

function run($for_date)
{
  global $etfs, $db_conn;

  echo "Run for " . $for_date . "\n";

  foreach ($etfs as $etf) {

    $db_conn->begin_transaction();

    try {

      echo 'Processing ' . $etf . "\n";

      // Check if this ETF for today has been processed
      $process_query = "SELECT id FROM process_logs WHERE etf = ? AND date = ? AND status = 'processed' LIMIT 1";
      $process_stmt = $db_conn->prepare($process_query);
      $process_stmt->bind_param('ss', $etf, $for_date);
      if (!$process_stmt->execute()) {
        $exception = "Process query failed: (" . $process_stmt->errno . ") " . $process_stmt->error;
        catch_exception($exception);
      }
      $process_result = $process_stmt->get_result();
      $process_stmt->close();

      if ($process_result->num_rows) {
        echo "Already processed, skipping\n";
        continue;
      }

      // Check if file exists
      list($year, $month) = explode('-', $for_date);
      $file = "./" . $etf . '/' . $year . '/' . $month . '/' . $etf . '-' . $for_date . '.csv';
      if (!file_exists($file)) {
        $exception = "File " . $file . " is not found\n";
        catch_exception($exception);
      }

      echo "Reading file " . $file . "\n";

      // Read file
      if (($handle = fopen($file, "r")) !== FALSE) {

        echo "Opened file for reading\n";

        $rows = 0;
        $processed_count = 0;

        while (($data = fgetcsv($handle, 3000, ",")) !== FALSE) {
          if ($rows++ === 0) {
            continue;
          }

          list($date, $fund, $company, $ticker, $cusip, $shares, $market_value, $weight) = $data;

          // No more row
          if (empty($date)) {
            break;
          }

          $date = new DateTime($date);
          $date = $date->format('Y-m-d');

          // Check if company exists
          $company_id = null;

          $company_query = "SELECT id FROM companies WHERE cusip = ?";
          $company_stmt = $db_conn->prepare($company_query);
          $company_stmt->bind_param('s', $cusip);
          if (!$company_stmt->execute()) {
            $exception = "Company query failed: (" . $company_stmt->errno . ") " . $company_stmt->error;
            catch_exception($exception);
          }

          $company_result = $company_stmt->get_result();
          $company_stmt->close();

          if ($company_result->num_rows) {
            $company_row = $company_result->fetch_assoc();
            $company_id = $company_row['id'];
          } else {
            $new_company_stmt = $db_conn->prepare('INSERT INTO companies(company, ticker, cusip) VALUES (?,?,?)');
            $new_company_stmt->bind_param('sss', $company, $ticker, $cusip);
            if (!$new_company_stmt->execute()) {
              $exception = "Company query failed: (" . $new_company_stmt->errno . ") " . $new_company_stmt->error;
              catch_exception($exception);
            }
            $company_id = $new_company_stmt->insert_id;
            echo "Create new company #" . $company_id . " " . $company . "\n";
          }

          if (!$company_id) {
            $exception = "Comany ID is not set\n";
            catch_exception($exception);
          }

          // Insert record
          $trade_stmt = $db_conn->prepare("INSERT INTO $etf (company_id, date, shares, market_value, weight) VALUES (?,?,?,?,?)");
          $trade_stmt->bind_param('isddd', $company_id, $date, $shares, $market_value, $weight);
          if (!$trade_stmt->execute()) {
            $exception = "Trade query failed: (" . $trade_stmt->errno . ") " . $trade_stmt->error;
            catch_exception($exception);
          }
          $trade_stmt->close();

          echo "Inserted record for company #" . $company_id . "\n";

          $processed_count++;
        }

        $process_stmt = $db_conn->prepare("INSERT INTO process_logs (etf, date, status, message) VALUES (?,?,'processed','Successfully processed')");
        $process_stmt->bind_param('ss', $etf, $for_date);
        $process_stmt->execute();
        $process_stmt->close();

        fclose($handle);

        $db_conn->commit();

        echo "Total " .  $processed_count . " processed\n";
      }
    } catch (db_conn_sql_exception $exception) {

      $db_conn->rollback();

      $process_stmt = $db_conn->prepare("INSERT INTO process_logs (etf, date, status, message) VALUES (?,?, 'error', ?)");
      $process_stmt->bind_param('sss', $etf, $for_date, $exception->getMessage());
      $process_stmt->execute();
      $process_stmt->close();

      throw $exception;
    }
  }
}

function catch_exception($message) {
  throw new exception($message);
}
