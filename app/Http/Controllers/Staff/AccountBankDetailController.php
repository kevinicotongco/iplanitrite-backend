<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Dto\Response\AccountBankDetailsResponseDto;
use App\Http\Requests\AccountBankDetailRequest;
use App\Models\AccountBankDetail;
use App\Services\Staff\AccountBankDetailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class AccountBankDetailController extends Controller
{
    public function index(AccountBankDetailService $bankDetailService): JsonResponse
    {
        $response = $bankDetailService->getBankDetails()->map(
            fn(AccountBankDetail $bankDetail): array => AccountBankDetailsResponseDto::fromModel($bankDetail)->toArray()
        );

        return response()->json($response->values()->toArray(), 200);
    }

    /**
     * @throws Throwable
     */
    public function store(AccountBankDetailRequest $request, AccountBankDetailService $bankDetailService): JsonResponse
    {
        $dto = $request->toDto();

        $bankDetail = DB::transaction(
            fn(): AccountBankDetail => $bankDetailService->createBankDetail($dto)
        );

        return response()->json(AccountBankDetailsResponseDto::fromModel($bankDetail)->toArray(), 201);
    }

    /**
     * @throws Throwable
     */
    public function update(
        string $bankDetailId,
        AccountBankDetailRequest $request,
        AccountBankDetailService $bankDetailService
    ): JsonResponse {
        $dto = $request->toDto();

        $bankDetail = DB::transaction(
            fn(): AccountBankDetail => $bankDetailService->updateBankDetail($bankDetailId, $dto)
        );

        return response()->json(AccountBankDetailsResponseDto::fromModel($bankDetail)->toArray(), 200);
    }

    /**
     * @throws Throwable
     */
    public function destroy(string $bankDetailId, AccountBankDetailService $bankDetailService): JsonResponse
    {
        DB::transaction(function () use ($bankDetailId, $bankDetailService): void {
            $bankDetailService->deleteBankDetail($bankDetailId);
        });

        return response()->json(null, 200);
    }
}
