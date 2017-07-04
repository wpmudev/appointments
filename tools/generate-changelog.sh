#!/bin/bash

if [[ $# -eq 0 ]] ; then
    echo "Please enter a date in YYYY-MM-DD format:"
    read DATE
else
	DATE=$1
fi

GIT_DIR=$(dirname "$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )" )

git log --pretty=format:'- %s' --since=$DATE > $GIT_DIR/log.log

echo "The log has been output to $GIT_DIR/log.log"
exit 0
