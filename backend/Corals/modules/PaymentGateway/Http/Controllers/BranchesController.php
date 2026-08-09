<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers;

use Corals\Foundation\Facades\Hashids;
use Corals\Foundation\Http\Controllers\BaseController;
use Corals\Modules\PaymentGateway\DataTables\BranchesDataTable;
use Corals\Modules\PaymentGateway\Http\Requests\BranchRequest;
use Corals\Modules\PaymentGateway\Models\Branch;
use Corals\Modules\PaymentGateway\Models\OperatorBranch;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\Modules\PaymentGateway\Services\BranchService;
use Corals\User\Models\User;
use Illuminate\Http\Request;

class BranchesController extends BaseController
{
    protected $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;

        $this->resource_url = config('paymentgateway.models.branch.resource_url');

        $this->resource_model = new Branch();

        $this->title = trans('PaymentGateway::module.branch.title');
        $this->title_singular = trans('PaymentGateway::module.branch.title_singular');

        parent::__construct();
    }

    public function index(BranchRequest $request, BranchesDataTable $dataTable)
    {
        return $dataTable->render('PaymentGateway::branches.index');
    }

    public function create(BranchRequest $request)
    {
        $branch = new Branch();
        $stores = Store::query()->orderBy('name')->get();

        // Pre-select the store when arriving from a Store's Branches panel.
        $selectedStoreId = $request->get('store_id');

        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.create_title', ['title' => $this->title_singular]),
        ]);

        return view('PaymentGateway::branches.create_edit')->with(compact('branch', 'stores', 'selectedStoreId'));
    }

    public function store(BranchRequest $request)
    {
        try {
            $store = Store::findByHash($request->get('store_id'));

            abort_if(!$store, 404);

            $branch = $this->branchService->store($request, Branch::class, ['store_id' => $store->id]);

            flash(trans('Corals::messages.success.created', ['item' => $this->title_singular]))->success();
        } catch (\Exception $exception) {
            log_exception($exception, Branch::class, 'store');
        }

        return redirectTo(isset($branch) ? $branch->getShowURL() : $this->resource_url);
    }

    public function show(BranchRequest $request, Branch $branch)
    {
        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.show_title', ['title' => $branch->getIdentifier()]),
            'showModel' => $branch,
        ]);

        // Users not yet assigned as operators here - the "Add operator" select.
        // ponytail: loads all unassigned users; add search/autocomplete when the
        // user base outgrows a plain <select>.
        $assignableUsers = User::query()
            ->whereNotIn('id', $branch->operators()->pluck('users.id'))
            ->orderBy('name')
            ->get();

        return view('PaymentGateway::branches.show')->with(compact('branch', 'assignableUsers'));
    }

    public function edit(BranchRequest $request, Branch $branch)
    {
        $stores = Store::query()->orderBy('name')->get();

        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.update_title', ['title' => $branch->getIdentifier()]),
        ]);

        return view('PaymentGateway::branches.create_edit')->with(compact('branch', 'stores'));
    }

    public function update(BranchRequest $request, Branch $branch)
    {
        try {
            $store = Store::findByHash($request->get('store_id'));

            abort_if(!$store, 404);

            $this->branchService->update($request, $branch, ['store_id' => $store->id]);

            flash(trans('Corals::messages.success.updated', ['item' => $this->title_singular]))->success();
        } catch (\Exception $exception) {
            log_exception($exception, Branch::class, 'update');
        }

        return redirectTo($branch->getShowURL());
    }

    public function destroy(BranchRequest $request, Branch $branch)
    {
        try {
            $this->branchService->destroy($request, $branch);

            $message = [
                'level' => 'success',
                'message' => trans('Corals::messages.success.deleted', ['item' => $this->title_singular]),
            ];
        } catch (\Exception $exception) {
            log_exception($exception, Branch::class, 'destroy');
            $message = ['level' => 'error', 'message' => $exception->getMessage()];
        }

        return response()->json($message);
    }

    /**
     * Assign a User as an operator of this branch (lets them POST /pos/login
     * against it). Not a BranchRequest: that form request treats every POST as
     * a "create" and would check branch.create instead of branch.update.
     */
    public function assignOperator(Request $request, Branch $branch)
    {
        $this->authorize('update', $branch);

        $request->validate(['user_id' => ['required']]);

        $userId = Hashids::decode($request->get('user_id'))[0] ?? null;

        abort_if(!$userId || !User::query()->whereKey($userId)->exists(), 404);

        OperatorBranch::firstOrCreate(['user_id' => $userId, 'branch_id' => $branch->id]);

        flash(trans('PaymentGateway::module.branch.operator_assigned'))->success();

        return back();
    }

    /**
     * Remove a User's operator access to this branch.
     */
    public function removeOperator(Branch $branch, string $user)
    {
        $this->authorize('update', $branch);

        $userId = Hashids::decode($user)[0] ?? null;

        abort_if(!$userId, 404);

        $branch->operators()->detach($userId);

        flash(trans('PaymentGateway::module.branch.operator_removed'))->success();

        return back();
    }
}
