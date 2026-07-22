#!/bin/bash

web_root="/var/www/html/admin"
# 변수 설정 (hellousa 인트라넷 표준 가이드)
DB_HOST="database-1.c6dioccwsg78.us-east-1.rds.amazonaws.com"  # 리모트 서버 IP 또는 도메인
DB_PORT="3306"                  # MySQL 기본 포트
db_user="admin"
db_pass="Lee10011!!"
db_name="dbs13437728"
backup_dir="../backups"
date_format="%Y%m%d_%H%M%S"
compress="yes"
max_backups=7

# FTP 설정
ftp_server="71.172.123.161"
ftp_user="wincom00"
ftp_password="Lee10011!"
ftp_remote_dir="/hellobak"

error_exit() {
    echo "Error: $1" >&2
    exit 1
}

backup_dir=$(realpath "$backup_dir") || error_exit "Invalid backup directory."

# 백업 디렉토리가 없으면 생성
if [ ! -d "$backup_dir" ]; then
 sudo mkdir -p "$backup_dir" || error_exit "Failed to create backup directory."
fi


now=$(date +"$date_format")
backup_file_prefix="website_backup_$now"

# 백업 디렉토리가 이미 존재하는지 확인
backup_dir_with_date="$backup_dir/$backup_file_prefix"  #날짜별 백업디렉토리

if [ -d "$backup_dir_with_date" ]; then
  echo "Backup directory '$backup_dir_with_date' already exists. Skipping backup."
  exit 0  # 이미 존재하면 스크립트 종료
fi

# 백업 디렉토리가 없으면 생성 (날짜별 디렉토리)
sudo mkdir -p "$backup_dir_with_date" || error_exit "Failed to create dated backup directory."

web_backup_file="$backup_dir_with_date/$backup_file_prefix.tar"
db_backup_file="$backup_dir_with_date/$backup_file_prefix.sql"

[ "$compress" = "yes" ] && sudo tar -czvf "$web_backup_file.gz" -C "$web_root" . || sudo tar -cvf "$web_backup_file" -C "$web_root" . || error_exit "Failed to create website backup."

# 리모트 백업 실행
: "${DB_PASSWORD:=$db_pass}"
sudo mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$db_user" -p"$DB_PASSWORD" \
--default-character-set=utf8mb4 "$db_name" > "$db_backup_file" \
|| error_exit "Failed to create remote database backup."
[ "$compress" = "yes" ] && gzip "$db_backup_file" || :

#find "$backup_dir" -name "$backup_file_prefix*" -type f -mtime +"$((max_backups - 1))" -delete

# FTP 전송
ftp_upload() {
    local file="$1"
    local remote_file="$2"
    local success=0 

ftp -n "$ftp_server" <<EOF
user "$ftp_user" "$ftp_password"
cd "$ftp_remote_dir"
put "$file" "$remote_file"
bye
EOF

    if [ $? -eq 0 ]; then
        echo "FTP upload successful for $file."
        success=1
    else
        error_exit "FTP upload failed for $file."
    fi

    # 전송 성공 시 로컬 파일 삭제
    if [ "$success" -eq 1 ]; then
       # sudo rm -f "$file" || error_exit "Failed to delete local file: $file" #sudo 추가
    fi
}


# 웹사이트 백업 파일 FTP 전송
web_remote_file="${backup_file_prefix}.tar$( [ "$compress" = "yes" ] && echo ".gz" )"
ftp_upload "$web_backup_file$( [ "$compress" = "yes" ] && echo ".gz" )" "$web_remote_file"

# 데이터베이스 백업 파일 FTP 전송
db_remote_file="${backup_file_prefix}.sql$( [ "$compress" = "yes" ] && echo ".gz" )"
ftp_upload "$db_backup_file$( [ "$compress" = "yes" ] && echo ".gz" )" "$db_remote_file"

echo "Backup and FTP upload completed successfully."
echo "Website: $web_backup_file$( [ "$compress" = "yes" ] && echo ".gz" )"
echo "Database: $db_backup_file$( [ "$compress" = "yes" ] && echo ".gz" )"

exit 0