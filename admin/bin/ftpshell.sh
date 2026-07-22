#!/bin/sh


###########################################################
# ftp -inv target_ip << EOF                               #
# user username password                                  #
# bin                                                     #
# prom                                                    #
# cd directory_path                                       #
# pwd                                                     #
# mput file_name                                          #
# EOF                                                     #
###########################################################

YY=0 
MM=0 
DD=0 

YY=$(date +%0y) 
MM=$(date +%0m) 
DD=$(date +%0d) 

# --- 설정 변수 ---
REMOTE_USER="wincom00"
REMOTE_HOST="access886660443.webspace-data.io"
REMOTE_PASS="Lee10011!"  # 여기에 실제 비밀번호를 입력!


if [ $YY -lt 10 ] ; 
then 
        NYY=$YY 
else 
        NYY=$YY 
fi 

if [ $MM -lt 10 ] ; 
then 
        NMM=$MM 
else 
        NMM=$MM 
fi 

if [ $DD -lt 10 ] ; 
then 
        NDAY=$DD 
else 
        NDAY=$DD 
fi 

cd ../backups/


# --- 스크립트 실행 부분 ---
echo "SFTP start."

# sshpass를 사용하여 비밀번호를 전달하고 sftp 실행
sshpass -p 'Lee10011!' sftp -o StrictHostKeyChecking=no "u106066278@access886660443.webspace-data.io" <<-EOF
  cd /backups/
  mkdir "20"$NYY$NMM$NDAY"admin" 
  cd "20"$NYY$NMM$NDAY"admin"  
  put sql-"20"$NYY"-"$NMM"-"$NDAY.sql.bz2 
  put webf-"20"$NYY"-"$NMM"-"$NDAY.tar.bz2 
  ls
  
  quit
EOF

rm -f sql-"20"$NYY"-"$NMM"-"$NDAY.sql.bz2 webf-"20"$NYY"-"$NMM"-"$NDAY.tar.bz2
echo "SFTP complete"
