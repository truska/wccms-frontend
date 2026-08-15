#!/usr/bin/env bash
set -euo pipefail

SCRIPT="/var/www/dev-wc.witecanvas.com/web/tools/SetColours"
LOGFILE="/tmp/setcolours.log"
MORNING_TIME="${1:-07:15}"

if [ ! -x "${SCRIPT}" ]; then
  echo "Missing executable script: ${SCRIPT}" >&2
  exit 1
fi

if ! [[ "${MORNING_TIME}" =~ ^([01][0-9]|2[0-3]):[0-5][0-9]$ ]]; then
  echo "Invalid time '${MORNING_TIME}'. Use HH:MM (24h), e.g. 07:15" >&2
  exit 1
fi

HOUR="${MORNING_TIME%:*}"
MINUTE="${MORNING_TIME#*:}"

TMP_CRON="$(mktemp)"
trap 'rm -f "${TMP_CRON}"' EXIT

crontab -l 2>/dev/null | grep -v 'tools/SetColours all' > "${TMP_CRON}" || true

echo "@reboot ${SCRIPT} all >> ${LOGFILE} 2>&1" >> "${TMP_CRON}"
echo "${MINUTE} ${HOUR} * * * ${SCRIPT} all >> ${LOGFILE} 2>&1" >> "${TMP_CRON}"

crontab "${TMP_CRON}"

echo "Installed SetColours cron jobs:"
echo "  @reboot"
echo "  daily at ${MORNING_TIME}"
