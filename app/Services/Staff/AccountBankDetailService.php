<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\Auth\StaffAuthenticatedUser;
use App\Dto\Request\AccountBankDetailRequestDto;
use App\Models\AccountBankDetail;
use Illuminate\Support\Collection;

readonly class AccountBankDetailService
{
    public function __construct(
        private StaffAuthenticatedUser $authenticatedUser,
        private AccountBankDetail $bankDetailModel,
    ) {}

    /**
     * @return Collection<int, AccountBankDetail>
     */
    public function getBankDetails(): Collection
    {
        return $this->bankDetailModel::where('account_id', $this->authenticatedUser->accountId)
            ->orderBy('created_at')
            ->get();
    }

    public function findBankDetailForAccount(string $bankDetailId): ?AccountBankDetail
    {
        return $this->bankDetailModel::where('id', $bankDetailId)
            ->where('account_id', $this->authenticatedUser->accountId)
            ->first();
    }

    public function createBankDetail(AccountBankDetailRequestDto $dto): AccountBankDetail
    {
        return $this->bankDetailModel::create([
            'account_id' => $this->authenticatedUser->accountId,
            'bank_name' => $dto->bankName,
            'account_number' => $dto->accountNumber,
            'qr_code' => $dto->qrCode,
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);
    }

    public function updateBankDetail(string $bankDetailId, AccountBankDetailRequestDto $dto): AccountBankDetail
    {
        $bankDetail = $this->getBankDetailForAccount($bankDetailId);

        $bankDetail->update([
            'bank_name' => $dto->bankName,
            'account_number' => $dto->accountNumber,
            'qr_code' => $dto->qrCode,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        return $bankDetail;
    }

    public function deleteBankDetail(string $bankDetailId): void
    {
        $this->getBankDetailForAccount($bankDetailId)->delete();
    }

    private function getBankDetailForAccount(string $bankDetailId): AccountBankDetail
    {
        return $this->bankDetailModel::where('id', $bankDetailId)
            ->where('account_id', $this->authenticatedUser->accountId)
            ->firstOrFail();
    }
}
