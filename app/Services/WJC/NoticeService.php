<?php

namespace App\Services\WJC;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\Notice;
use App\Traits\LogTrait;

class NoticeService
{
    use LogTrait;

    /**
     * 创建公告
     */
    public function create(int $adminId, array $data): array
    {
        $notice = Notice::create([
            'title'        => $data['title'],
            'content'      => $data['content'],
            'cover'        => $data['cover'] ?? '',
            'is_top'       => $data['isTop'] ?? 0,
            'status'       => 1,
            'create_admin' => $adminId,
            'create_time'  => now(),
        ]);

        $this->logBusiness('管理员创建公告', [
            'admin_id'  => $adminId,
            'notice_id' => $notice->notice_id,
        ]);

        return [
            'noticeId' => $notice->notice_id,
            'title'    => $notice->title,
        ];
    }

    /**
     * 删除公告
     */
    public function delete(int $noticeId): void
    {
        $notice = Notice::find($noticeId);

        if (!$notice) {
            throw new BusinessException('公告不存在', ResponseCode::DATA_NOT_FOUND);
        }

        /** @var \App\Models\SysAdmin $admin */
        $admin = auth('admin_api')->user();

        $notice->delete();

        $this->logBusiness('管理员删除公告', [
            'admin_id'  => $admin->admin_id,
            'notice_id' => $noticeId,
        ]);
    }

    /**
     * 更新公告
     */
    public function update(int $noticeId, array $data): array
    {
        $notice = Notice::find($noticeId);

        if (!$notice) {
            throw new BusinessException('公告不存在', ResponseCode::DATA_NOT_FOUND);
        }

        $updateData = [];
        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
        }
        if (isset($data['content'])) {
            $updateData['content'] = $data['content'];
        }
        if (isset($data['cover'])) {
            $updateData['cover'] = $data['cover'];
        }
        if (isset($data['isTop'])) {
            $updateData['is_top'] = (int) $data['isTop'];
        }
        if (isset($data['status'])) {
            $updateData['status'] = (int) $data['status'];
        }

        if (!empty($updateData)) {
            $updateData['update_time'] = now();
            $notice->update($updateData);
        }

        /** @var \App\Models\SysAdmin $admin */
        $admin = auth('admin_api')->user();

        $this->logBusiness('管理员更新公告', [
            'admin_id'  => $admin->admin_id,
            'notice_id' => $noticeId,
        ]);

        return [
            'noticeId' => $notice->notice_id,
            'title'    => $notice->title,
        ];
    }

    /**
     * 后台获取公告分页列表（含已下架）
     */
    public function list(int $page = 1, int $size = 10, ?string $title = null): array
    {
        $query = Notice::orderBy('is_top', 'desc')
            ->orderBy('create_time', 'desc');

        if ($title) {
            $query->where('title', 'like', "%{$title}%");
        }

        $total = $query->count();
        $list  = $query->forPage($page, $size)->get()->map(function (Notice $notice) {
            return [
                'noticeId'    => $notice->notice_id,
                'title'       => $notice->title,
                'isTop'       => $notice->is_top,
                'status'      => $notice->status,
                'createAdmin' => $notice->create_admin,
                'createTime'  => $notice->create_time,
            ];
        });

        return [
            'total' => $total,
            'list'  => $list->values(),
        ];
    }

    /**
     * 后台获取公告详情
     */
    public function detail(int $noticeId): array
    {
        $notice = Notice::find($noticeId);

        if (!$notice) {
            throw new BusinessException('公告不存在', ResponseCode::DATA_NOT_FOUND);
        }

        return [
            'noticeId'   => $notice->notice_id,
            'title'      => $notice->title,
            'content'    => $notice->content,
            'cover'      => $notice->cover,
            'isTop'      => $notice->is_top,
            'status'     => $notice->status,
            'createTime' => $notice->create_time,
        ];
    }

    /**
     * 前台获取公告列表（仅上架公告）
     */
    public function frontList(int $page = 1, int $size = 10): array
    {
        $query = Notice::enabled()
            ->orderBy('is_top', 'desc')
            ->orderBy('create_time', 'desc');

        $total = $query->count();
        $list  = $query->forPage($page, $size)->get()->map(function (Notice $notice) {
            return [
                'noticeId'   => $notice->notice_id,
                'title'      => $notice->title,
                'cover'      => $notice->cover,
                'isTop'      => $notice->is_top,
                'createTime' => $notice->create_time,
            ];
        });

        return [
            'total' => $total,
            'list'  => $list->values(),
        ];
    }

    /**
     * 前台获取公告详情（仅上架公告）
     */
    public function frontDetail(int $noticeId): array
    {
        $notice = Notice::enabled()->find($noticeId);

        if (!$notice) {
            throw new BusinessException('公告不存在或已下架', ResponseCode::DATA_NOT_FOUND);
        }

        return [
            'noticeId'   => $notice->notice_id,
            'title'      => $notice->title,
            'content'    => $notice->content,
            'cover'      => $notice->cover,
            'createTime' => $notice->create_time,
        ];
    }
}
