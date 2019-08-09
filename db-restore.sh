#!/bin/bash
PSQL="sudo -u postgres psql "
$PSQL < db/dev.sql
$PSQL -U skyflow -h localhost -d skyflow < db/install.sql
$PSQL -U skyflow -h localhost -d skyflow < db/examples.sql
