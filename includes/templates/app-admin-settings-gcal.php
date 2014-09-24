<?php
global $appointments;
if (is_object($appointments->gcal_api)) $appointments->gcal_api->render_tab();