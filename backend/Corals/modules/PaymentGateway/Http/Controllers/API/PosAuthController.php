<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers\API;

use Corals\Foundation\Http\Controllers\APIPublicController;
use Corals\Modules\PaymentGateway\Models\OperatorStore;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PosAuthController extends APIPublicController
{
    /**
     * Log in a POS operator and issue a Sanctum token scoped to one store.
     *
     * Abilities: payment:lookup, payment:collect, transaction:read-own,
     * shift:manage, plus a synthetic `store:{hashid}` ability that scopes
     * this token to the requested store (checked by ShiftsController@store).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'store_id' => ['required'],
        ]);

        $user = User::query()->where('email', $request->get('email'))->first();

        if (!$user || !Hash::check($request->get('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => [trans('auth.failed')]]);
        }

        $store = Store::findByHash($request->get('store_id'));

        if (!$store) {
            throw ValidationException::withMessages(['store_id' => [trans('Corals::messages.errors.not_found')]]);
        }

        $isAssigned = OperatorStore::query()
            ->where('user_id', $user->id)
            ->where('store_id', $store->id)
            ->exists();

        if (!$isAssigned) {
            throw ValidationException::withMessages(['store_id' => ['This operator is not assigned to the requested store.']]);
        }

        $abilities = [
            'payment:lookup',
            'payment:collect',
            'transaction:read-own',
            'shift:manage',
            'store:' . $store->getHashedIdAttribute(),
        ];

        $token = $user->createToken('pos-operator', $abilities);

        return apiResponse([
            'token' => $token->plainTextToken,
            'abilities' => $abilities,
            'store_id' => $store->getHashedIdAttribute(),
        ]);
    }
}
