
이미지 자동 첨부 애드온
-----------------------

본문에 포함된 이미지 중 첨부파일이 아닌 것을 첨부파일로 자동 변환해 주는 XE 애드온입니다.
첨부한 이미지와 외부 이미지를 구분하지 못하는 사용자들이 많고,
SSL을 적용한 사이트에서 SSL을 적용하지 않은 이미지를 불러올 경우 보안 경고가 뜨는 등의 문제를 해결할 수 있으나,
**다른 사이트의 이미지를 함부로 복사하면 저작권 침해가 될 수 있으므로 사용시 주의하시기 바랍니다.**

새로 작성하는 글이 아닌 기존 문서도 조회하는 시점에 첨부파일로 변환하는 기능이 있으나,
이 기능을 사용할 경우 페이지 로딩 시간이 길어질 수 있으니 주의하십시오.
기존 댓글의 이미지는 변환하지 않습니다.

### 기타 기능 안내

이미지가 많거나 원본 서버와의 접속이 원활하지 않아 최초 등록에 20초 이상 소요되는 경우,
타임아웃 오류 발생을 막기 위해 첨부파일 변환을 중단하도록 되어 있습니다.
이런 경우에는 다음에 수정하거나 (기존 문서 변환을 선택한 경우) 조회할 때 나머지를 처리하게 됩니다.

동일한 이미지를 같은 글에 여러 번 삽입하더라도 한 번만 첨부하여 처리 시간과 트래픽을 절약하고,
다운로드에 실패한 이미지를 무한정 재시도하지 않는 지능적인 애드온입니다.

각 모듈에서 설정한 개별 파일 용량 제한 및 첨부파일 용량 합계 제한의 적용을 받도록 설정할 수 있습니다.
최고관리자가 쓰거나 수정하는 글에는 적용되지 않습니다.

### 오류 확인

운영하시는 사이트와 첨부 대상 이미지가 위치한 서버의 상태에 따라 자동 첨부가 되지 않을 수도 있습니다.
오류가 발생하면 이미지 태그의 `data-autoattach` 속성에 오류 정보가 추가되니,
페이지 소스를 참고하여 오류의 원인을 파악하시기 바랍니다.

  - `download-failure` : 이미지를 다운로드할 수 없음
  - `download-timeout` : 이미지 다운로드 도중 타임아웃 발생 (2초 초과)
  - `size-limit-single` : 개별 파일 용량 제한 초과
  - `size-limit-total` : 첨부파일 용량 합계 제한 초과
  - `insert-error` : 첨부파일 정보를 DB에 저장하는 도중 에러 발생

### 라이선스

라이선스는 GPLv2입니다.
수정 재배포가 금지된 카르마 님의 "외부이미지 저장 애드온" 소스코드는 전혀 사용하지 않고 새로 구현했으며,
유사한 부분이 있더라도 유사한 기능을 구현하는 데 따르는 우연의 일치일 뿐임을 밝힙니다.
