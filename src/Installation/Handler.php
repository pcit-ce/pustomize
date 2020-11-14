<?php

declare(strict_types=1);

namespace PCIT\Pustomize\Installation;

use App\Repo;
use App\User;
use PCIT\GPI\Webhooks\Context\Components\InstallationRepositories;
use PCIT\GPI\Webhooks\Context\InstallationContext;

class Handler
{
    /**
     * created 用户点击安装按钮(首次).
     *
     * deleted 用户卸载了 GitHub Apps
     *
     * @param mixed $context
     */
    public function handle(InstallationContext $context): void
    {
        $installation_id = $context->installation->id;
        $action = $context->action;
        $repositories = $context->repositories;
        $sender = $context->sender;
        $account = $context->account;

        if ('new_permissions_accepted' === $action) {
            \Log::info('receive event [ installation ] action [ new_permissions_accepted ]');

            return;
        }

        if ('deleted' === $action) {
            $this->delete($installation_id, $account->login);

            return;
        }

        // 仓库管理员信息
        User::updateUserInfo((int) $sender->uid, null, $sender->username, null, $sender->pic);
        User::updateUserInfo($account);
        User::updateInstallationId((int) $installation_id, $account->login);
        $this->create($repositories, $sender->uid);
    }

    /**
     * 用户首次安装了 GitHub App.
     *
     * @param InstallationRepositories[] $repositories
     */
    public function create(array $repositories, int $sender_uid): void
    {
        foreach ($repositories as $k) {
            // 仓库信息存入 repo 表
            $rid = $k->id;

            $repo_full_name = $k->full_name;
            $private = $k->private;

            Repo::updateRepoInfo(
                (int) $rid,
                $repo_full_name,
                $sender_uid,
                null,
                null,
                $private,
            );
        }
    }

    /**
     * 用户卸载了 GitHub App.
     */
    public function delete(int $installation_id, string $username): void
    {
        Repo::deleteByInstallationId($installation_id);
        User::updateInstallationId(0, $username);
    }
}
