<?php

namespace Emarka\Sms;

interface StateInterface
{
    public function code();
    public function state();
    public function description();
    public function __toString();
}
