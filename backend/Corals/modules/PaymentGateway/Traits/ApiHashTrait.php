<?php

namespace Corals\Modules\PaymentGateway\Traits;

use Corals\Foundation\Facades\Hashids;

/**
 * Corals\Foundation\Traits\HashTrait (inherited via BaseModel) treats any request
 * under the 'api' middleware group as a pass-through: hashids_encode()/
 * hashids_decode() just return the value unchanged there, so hashed_id/findByHash/
 * resolveRouteBinding silently expose the raw BIGINT primary key on every real API
 * call instead of a hashid string - violating docs/api-contract.md's "Hashid
 * strings, never the raw PK" rule. Corals/core is vendor-managed and gets
 * overwritten on framework refreshes (see git history), so the fix can't live
 * there - these overrides call the Hashids facade directly, skipping that
 * request-context check, for the PaymentGateway models that cross the API.
 */
trait ApiHashTrait
{
    public function getHashedIdAttribute()
    {
        return Hashids::encode($this->{$this->getRouteKeyName()});
    }

    public static function findByHash($value)
    {
        if (!$value) {
            return null;
        }

        $decoded = Hashids::decode($value);

        return $decoded ? self::find($decoded[0]) : null;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if (in_array($field, $this->allowedFields ?? [])) {
            return $this->where($field, $value)->first();
        }

        $decoded = Hashids::decode($value);

        return $decoded ? $this->where($this->getRouteKeyName(), $decoded[0])->first() : null;
    }
}
