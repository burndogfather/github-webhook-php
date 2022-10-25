# github-webhook-php
깃헙 웹훅을 이용하여 깃헙의 커밋에 따라 실제 파일을 반영하는 CI/CD 자동화 소스입니다.
PHP로 작성된 서비스들은 별도 컴파일이 불필요함으로 CI/CD를 위해 젠킨스등을 활용할 필요없이 즉시에 서버배포가 가능합니다.
이 점을 활용하여 Git에 Commit을 통해 파일을 추가/수정/삭제할때 원격지 서버로 변경사항을 실시간 반영이 가능합니다.
서버에 별도의 SVN이나 SSH, FTP등을 통해 배포할 필요가 없고, REST API기반으로


<img width="1435" alt="스크린샷 2022-10-26 오전 2 04 02" src="https://user-images.githubusercontent.com/101985768/197838089-77055441-a948-4fc9-8652-414079121dc1.png">

