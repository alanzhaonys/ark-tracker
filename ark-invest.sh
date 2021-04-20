#/bin/bash

today=$(date +"%m-%d-%Y")
day_of_week=$(date +"%u")
etfs=("ARKK" "ARKQ" "ARKW" "ARKG" "ARKF" "ARKX")
download_urls=(
  "https://ark-funds.com/wp-content/fundsiteliterature/csv/ARK_INNOVATION_ETF_ARKK_HOLDINGS.csv"
  "https://ark-funds.com/wp-content/fundsiteliterature/csv/ARK_AUTONOMOUS_TECHNOLOGY_&_ROBOTICS_ETF_ARKQ_HOLDINGS.csv"
  "https://ark-funds.com/wp-content/fundsiteliterature/csv/ARK_NEXT_GENERATION_INTERNET_ETF_ARKW_HOLDINGS.csv"
  "https://ark-funds.com/wp-content/fundsiteliterature/csv/ARK_GENOMIC_REVOLUTION_MULTISECTOR_ETF_ARKG_HOLDINGS.csv"
  "https://ark-funds.com/wp-content/fundsiteliterature/csv/ARK_FINTECH_INNOVATION_ETF_ARKF_HOLDINGS.csv"
  "https://ark-funds.com/wp-content/fundsiteliterature/csv/ARK_SPACE_EXPLORATION_&_INNOVATION_ETF_ARKX_HOLDINGS.csv"
)

# Skip weekend
if [ $day_of_week eq 6 || $day_of_week eq 7 ]; then
  echo "No run for weekend"
  exit
fi

echo Running crawler for $today

for i in "${!etfs[@]}"
do
  etf=${etfs[i]}
  download_url=${download_urls[i]}

  echo $etf
  echo $download_url

  # Download and preserve the file timestamp using -R
  echo "----"
  curl --user-agent 'Chrome/79' -R $download_url -o /tmp/${etf}.csv
  echo "----"
  echo Remote file created on $(date -r /tmp/ARKK.csv +"%Y-%m-%d %H:%M:%S")

  # Get file timestamp info
  file_year=$(date -r /tmp/ARKK.csv +"%Y")
  file_month=$(date -r /tmp/ARKK.csv +"%m")
  file_full_date=$(date -r /tmp/ARKK.csv +"%Y-%m-%d")

  # Make sure directories exist
  mkdir -p ./${etf}
  mkdir -p ./${etf}/${file_year}
  mkdir -p ./${etf}/${file_year}/${file_month}

  # Finally save file
  file=./${etf}/${file_year}/${file_month}/${etf}-${file_full_date}.csv

  # cp -p to preserve the timestamp of the CSV file
  cp -p /tmp/${etf}.csv $file

  echo Created $file
  echo
  echo

done
