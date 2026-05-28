import request from '@/api/request'

/**
 * 学校管理 API
 */

// 获取学校列表
export function getSchoolList(params) {
  return request({
    url: '/admin/schools',
    method: 'get',
    params,
  })
}

// 获取学校详情
export function getSchoolDetail(id) {
  return request({
    url: `/admin/schools/${id}`,
    method: 'get',
  })
}

// 创建学校
export function createSchool(data) {
  return request({
    url: '/admin/schools',
    method: 'post',
    data,
  })
}

// 更新学校
export function updateSchool(id, data) {
  return request({
    url: `/admin/schools/${id}`,
    method: 'put',
    data,
  })
}

// 删除学校
export function deleteSchool(id) {
  return request({
    url: `/admin/schools/${id}`,
    method: 'delete',
  })
}

// 获取所有启用的学校
export function getActiveSchools() {
  return request({
    url: '/admin/schools/active',
    method: 'get',
  })
}

/**
 * 食堂管理 API
 */

// 获取学校食堂列表
export function getCanteenList(schoolId, params) {
  return request({
    url: `/admin/schools/${schoolId}/canteens`,
    method: 'get',
    params,
  })
}

// 获取食堂详情
export function getCanteenDetail(schoolId, id) {
  return request({
    url: `/admin/schools/${schoolId}/canteens/${id}`,
    method: 'get',
  })
}

// 创建食堂
export function createCanteen(schoolId, data) {
  return request({
    url: `/admin/schools/${schoolId}/canteens`,
    method: 'post',
    data,
  })
}

// 更新食堂
export function updateCanteen(schoolId, id, data) {
  return request({
    url: `/admin/schools/${schoolId}/canteens/${id}`,
    method: 'put',
    data,
  })
}

// 删除食堂
export function deleteCanteen(schoolId, id) {
  return request({
    url: `/admin/schools/${schoolId}/canteens/${id}`,
    method: 'delete',
  })
}

// 获取学校食堂（简化版）
export function getCanteensBySchool(schoolId) {
  return request({
    url: `/admin/schools/${schoolId}/canteens`,
    method: 'get',
  })
}