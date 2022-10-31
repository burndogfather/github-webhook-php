# github-webhook-php
깃헙 웹훅을 이용하여 깃헙의 커밋에 따라 실제 파일을 반영하는 CI/CD 자동화 소스입니다.  
PHP로 작성된 서비스들은 별도 컴파일이 불필요함으로 CI/CD를 위해 젠킨스등을 활용할 필요없이 즉시에 서버배포가 가능합니다.  
이 점을 활용하여 Git에 Commit을 통해 파일을 추가/수정/삭제할때 원격지 서버로 변경사항을 실시간 반영이 가능합니다.  
  
서버에 별도의 SVN이나 SSH, FTP등을 통해 배포할 필요가 없고, REST API기반으로 배포가 진행됨으로 80, 443포트가 개방된 환경이라면 어디든 손쉽게 원격지 서버 배포가 가능해집니다.  
  
  
자신의 리포지토리에서 Webhook을 설정하고 Secret key를 생성해야 정상적으로 사용할 수 있습니다.
<img width="1435" alt="스크린샷 2022-10-26 오전 2 04 02" src="https://user-images.githubusercontent.com/101985768/197838089-77055441-a948-4fc9-8652-414079121dc1.png">  
  
  
Github에서 들어오는 Webhook요청이 아닌 임의의 Webhook요청으로 인해 배포서버의 데이터가 변조될 수 있음으로 반드시 Secret key를 활용해야 합니다.
  
  
  
### 주요기능  
  
- 파일 추가 (디렉토리 생성 포함)  
- 파일 수정  
- 파일 삭제 (디렉토리 파일 검사 실시하여 삭제포함)  
- 병렬처리를 통해 대량데이터도 빠르게 반영합니다!  
  
  
  
### 파일구성
  
- @.setting.php > 설정데이터 저장  
- github.webhook.php > Github Webhook 요청이 되는 파일
- inputchk.php > 요청데이터의 Injection 공격 차단용 함수  
- post_multi.php > 대량데이터를 병렬로 처리하기 위한 CURL함수  
  
  
### 다음의 정보가 필요로 합니다.
  
- 리포지토리 OWNER 아이디  
- Personal access token값  
- 리포지토리 이름  
- 리포지토리의 Webhook secret key값  
  
   
  
  
  
사람들이 하도 젠킨스 노래를 부르길래 젠킨스를 도입하려다가 PHP 프로젝트에서는 별도의 빌드과정이 불필요하기도 하고 Over engineering인듯 하여 Github API도 써볼겸, 머리도 식힐겸, 작성하게 되었습니다.  
(젠킨스는 Go프로젝트에서 활용하는 걸로!)  