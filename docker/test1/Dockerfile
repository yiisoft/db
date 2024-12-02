FROM php:8.3-cli AS php

FROM gvenzl/oracle-free:23 AS oracle

COPY --from=php /usr/local/lib/php/ /usr/local/lib/php/
COPY --from=php /usr/local/include/php/ /usr/local/include/php/
COPY --from=php /usr/local/php/ /usr/local/php/
COPY --from=php /usr/local/bin/php /usr/local/bin/php
COPY --from=php /usr/local/bin/php-config /usr/local/bin/php-config
COPY --from=php /usr/local/etc/php/ /usr/local/etc/php/

COPY --from=php /usr/lib/x86_64-linux-gnu/*.so* /usr/lib/x86_64-linux-gnu/
COPY --from=php /usr/lib/x86_64-linux-gnu/*.so* /usr/lib64/
COPY --from=php /usr/bin/ldd /usr/bin/ldd

RUN chown -R 54321:54321 /opt/oracle/oradata && chmod 0777 /opt/oracle/oradata
