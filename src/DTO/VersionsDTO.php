<?php

namespace CodeDistortion\Adapt\DTO;

/**
 * A DTO to record the versions of various things.
 */
class VersionsDTO extends AbstractDTO
{
    /** @var string|null The PHP version. */
    public ?string $php = null;

    /** @var string|null The Laravel version. */
    public ?string $laravel = null;

    /** @var string|null The PHPUnit version. */
    public ?string $phpunit = null;

    /** @var string|null The Pest version. */
    public ?string $pest = null;

    /** @var string|null The Adapt version. */
    public ?string $adapt = null;

    /** @var string|null The MySQL version. */
    public ?string $mysql = null;

    /** @var string|null The PostgreSQL version. */
    public ?string $postgresql = null;

    /** @var string|null The PostgreSQL version. */
    public ?string $sqlite = null;



    /**
     * Set the PHP version.
     *
     * @param string|null $version The PHP version.
     * @return static
     */
    public function phpVersion(?string $version): self
    {
        $this->php = $version;
        return $this;
    }

    /**
     * Set the Laravel version.
     *
     * @param string|null $version The Laravel version.
     * @return static
     */
    public function laravelVersion(?string $version): self
    {
        $this->laravel = $version;
        return $this;
    }

    /**
     * Set the PHPUnit version.
     *
     * @param string|null $version The PHPUnit version.
     * @return static
     */
    public function phpUnitVersion(?string $version): self
    {
        $this->phpunit = $version;
        return $this;
    }

    /**
     * Set the Pest version.
     *
     * @param string|null $version The Pest version.
     * @return static
     */
    public function pestVersion(?string $version): self
    {
        $this->pest = $version;
        return $this;
    }

    /**
     * Set the Adapt version.
     *
     * @param string|null $version The Adapt version.
     * @return static
     */
    public function adaptVersion(?string $version): self
    {
        $this->adapt = $version;
        return $this;
    }

    /**
     * Set the MySQL version.
     *
     * @param string|null $version The MySQL version.
     * @return static
     */
    public function mysqlVersion(?string $version): self
    {
        $this->mysql = $version;
        return $this;
    }

    /**
     * Set the PostgreSQL version.
     *
     * @param string|null $version The PostgreSQL version.
     * @return static
     */
    public function postgresqlVersion(?string $version): self
    {
        $this->postgresql = $version;
        return $this;
    }

    /**
     * Set the SQLite version.
     *
     * @param string|null $version The SQLite version.
     * @return static
     */
    public function sqliteVersion(?string $version): self
    {
        $this->sqlite = $version;
        return $this;
    }



    /**
     * Render the versions, ready to be logged.
     *
     * @return array<string, string>
     */
    public function renderVersions(): array
    {
        return array_filter([
            'PHP:' => $this->php,
            'Laravel:' => $this->laravel,
            'PHPUnit:' => $this->phpunit,
            'Pest:' => $this->pest,
            'Adapt:' => $this->adapt,
            'MySQL:' => $this->mysql,
            'PostgreSQL:' => $this->postgresql,
            'SQLite:' => $this->sqlite,
        ]);
    }
}
