<?php

namespace muyomu\auth\base;

class Authenticator
{
    private string $siteCredentials;

    /**
     * @return string
     */
    public function getSiteCredentials(): string
    {
        return $this->siteCredentials;
    }

    /**
     * @param string $siteCredentials
     */
    public function setSiteCredentials(string $siteCredentials): void
    {
        $this->siteCredentials = $siteCredentials;
    }
}