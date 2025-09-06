<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Contracts\Domain as DomainContract;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Database\Concerns\InvalidatesTenantsResolverCache;
use Stancl\Tenancy\Events;

class Domain extends Model implements DomainContract
{
    use CentralConnection,
        InvalidatesTenantsResolverCache;

    protected $guarded = [];

    public function tenant()
    {
        return $this->belongsTo(config('tenancy.tenant_model'));
    }

    protected $dispatchesEvents = [
        'saving' => Events\SavingDomain::class,
        'saved' => Events\DomainSaved::class,
        'creating' => Events\CreatingDomain::class,
        'created' => Events\DomainCreated::class,
        'updating' => Events\UpdatingDomain::class,
        'updated' => Events\DomainUpdated::class,
        'deleting' => Events\DeletingDomain::class,
        'deleted' => Events\DomainDeleted::class,
    ];

    /**
     * Boot the trait for ensuring domain is not occupied.
     */
    public static function bootEnsuresDomainIsNotOccupied()
    {
        static::saving(function ($self) {
            if ($domain = $self->newQuery()->where('domain_name', $self->domain_name)->first()) {
                if ($domain->getKey() !== $self->getKey()) {
                    throw new \Stancl\Tenancy\Exceptions\DomainOccupiedByOtherTenantException($self->domain_name);
                }
            }
        });
    }

    /**
     * Boot the trait for converting domains to lowercase.
     */
    public static function bootConvertsDomainsToLowercase()
    {
        static::saving(function ($model) {
            $model->domain_name = strtolower($model->domain_name);
        });
    }
}
