<?php

if ($errorMessage) $PROTO->add("error",$errorMessage);
if ($successMessage) $PROTO->add("success",$successMessage);
if ($contactId) $PROTO->add("contactId",$contactId);
if ($taskId) $PROTO->add("taskId",$taskId);

$PROTO->output();
