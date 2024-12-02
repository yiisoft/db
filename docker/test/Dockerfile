FROM gvenzl/oracle-free:23 AS oracle

RUN chown -R 54321:54321 /opt/oracle/oradata && chmod 0777 /opt/oracle/oradata

RUN ls

# COPY ./container-entrypoint-custom.sh ./container-entrypoint-custom.sh
# RUN ["chmod", "+x", "container-entrypoint-custom.sh"]
# RUN ORACLE_PASSWORD=sys_user_password container-entrypoint-custom.sh

FROM php:8.3-cli AS php

COPY --from=oracle /opt/oracle/ /opt/oracle/
COPY --from=oracle /usr/bin/7zzs /usr/bin/7zzs

ENV PATH="$PATH:/opt/oracle/product/23ai/dbhomeFree/bin:/opt/oracle"

RUN apt-get update && apt-get install -y \
    # Oracle
    libaio1

ENV ORACLE_SID=FREE
ENV ORACLE_BASE=/opt/oracle
ENV ORACLE_HOME=/opt/oracle/product/23ai/dbhomeFree
ENV ORACLE_BASE_HOME=/opt/oracle/product/23ai/dbhomeFree
ENV NLS_LANG=.AL32UTF8
ENV ORACLE_BASE_CONFIG=/opt/oracle/product/23ai/dbhomeFree

RUN groupadd -g 54321 oracle
RUN useradd oracle -u 54321 -g 54321 -m -s /bin/bash
RUN chown -R 54321:54321 /opt/oracle

# ENTRYPOINT ["/opt/oracle/container-entrypoint.sh"]
