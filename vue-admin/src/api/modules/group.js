import request from '@/api/request'

/**
 * 分组管理 API
 */

// 获取分组列表
export function getGroupList(params) {
  return request({
    url: '/admin/group',
    method: 'get',
    params,
  })
}

// 获取分组详情
export function getGroupDetail(id) {
  return request({
    url: `/admin/group/${id}`,
    method: 'get',
  })
}

// 新增分组
export function createGroup(data) {
  return request({
    url: '/admin/group',
    method: 'post',
    data,
  })
}

// 编辑分组
export function updateGroup(id, data) {
  return request({
    url: `/admin/group/${id}`,
    method: 'put',
    data,
  })
}

// 删除分组
export function deleteGroup(id) {
  return request({
    url: `/admin/group/${id}`,
    method: 'delete',
  })
}

/**
 * 分组食堂管理 API
 */

// 获取分组食堂列表
export function getGroupCanteens(groupId) {
  return request({
    url: `/admin/group/${groupId}/canteens`,
    method: 'get',
  })
}

// 添加食堂到分组
export function addCanteenToGroup(groupId, data) {
  return request({
    url: `/admin/group/${groupId}/canteens`,
    method: 'post',
    data,
  })
}

// 从分组移除食堂
export function removeCanteenFromGroup(groupId, canteenId) {
  return request({
    url: `/admin/group/${groupId}/canteens/${canteenId}`,
    method: 'delete',
  })
}

// 设置主账号
export function setCanteenAudit(groupId, canteenId) {
  return request({
    url: `/admin/group/${groupId}/canteens/${canteenId}/set-audit`,
    method: 'put',
  })
}

// 移除主账号
export function removeCanteenAudit(groupId, canteenId) {
  return request({
    url: `/admin/group/${groupId}/canteens/${canteenId}/remove-audit`,
    method: 'put',
  })
}
