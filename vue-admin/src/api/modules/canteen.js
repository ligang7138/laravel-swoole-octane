import request from '@/api/request'

export function getActiveCanteens(params) {
  return request({
    url: '/admin/schools/0/canteens',
    method: 'get',
    params: {
      status: 1,
      ...params,
    },
  })
}
