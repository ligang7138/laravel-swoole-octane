<template>
  <div class="group-list">
    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :model="searchForm" inline>
        <el-form-item label="分组名称">
          <el-input
            v-model="searchForm.name"
            placeholder="请输入分组名称"
            clearable
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item label="状态">
          <el-select
            v-model="searchForm.status"
            placeholder="请选择状态"
            clearable
            style="width: 120px"
          >
            <el-option label="启用" :value="1" />
            <el-option label="禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <el-icon><Search /></el-icon>
            搜索
          </el-button>
          <el-button @click="handleReset">
            <el-icon><Refresh /></el-icon>
            重置
          </el-button>
          <el-button type="success" @click="handleAdd">
            <el-icon><Plus /></el-icon>
            新增分组
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 表格区域 -->
    <el-card class="table-card">
      <template #header>
        <div class="card-header">
          <span>分组列表</span>
        </div>
      </template>

      <el-table v-loading="loading" :data="tableData" border stripe>
        <el-table-column prop="id" label="ID" width="80" align="center" />
        <el-table-column prop="name" label="分组名称" min-width="150" />
        <el-table-column prop="parent_name" label="父分组" width="150" />
        <el-table-column prop="code" label="分组编码" width="120" />
        <el-table-column prop="canteen_count" label="食堂数量" width="100" align="center" />
        <el-table-column prop="status" label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="add_user" label="创建人" width="100" />
        <el-table-column prop="add_time" label="创建时间" width="180" align="center" />
        <el-table-column label="操作" width="250" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
            <el-button type="info" link @click="handleCanteens(row)">食堂管理</el-button>
            <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.pageSize"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
      />
    </el-card>

    <!-- 新增/编辑对话框 -->
    <el-dialog
      v-model="formDialogVisible"
      :title="formType === 'add' ? '新增分组' : '编辑分组'"
      width="500px"
      :close-on-click-modal="false"
    >
      <el-form :model="groupForm" label-width="100px" :rules="formRules" ref="formRef">
        <el-form-item label="分组名称" prop="name">
          <el-input v-model="groupForm.name" placeholder="请输入分组名称" maxlength="50" />
        </el-form-item>
        <el-form-item label="父分组">
          <el-select
            v-model="groupForm.pid"
            placeholder="请选择父分组"
            clearable
            style="width: 100%"
          >
            <el-option label="顶级分组" :value="0" />
            <el-option
              v-for="item in parentOptions"
              :key="item.id"
              :label="item.name"
              :value="item.id"
              :disabled="item.id === groupForm.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="分组编码">
          <el-input v-model="groupForm.code" placeholder="请输入分组编码" maxlength="30" />
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="groupForm.status">
            <el-radio :value="1">启用</el-radio>
            <el-radio :value="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="formDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">
          确定
        </el-button>
      </template>
    </el-dialog>

    <!-- 食堂管理对话框 -->
    <el-dialog
      v-model="canteenDialogVisible"
      title="食堂管理"
      width="800px"
      :close-on-click-modal="false"
    >
      <div class="canteen-header">
        <span>分组：{{ currentGroup.name }}</span>
        <el-button type="primary" size="small" @click="handleAddCanteen">
          添加食堂
        </el-button>
      </div>

      <el-table :data="canteenList" border stripe style="margin-top: 15px">
        <el-table-column prop="id" label="ID" width="80" align="center" />
        <el-table-column prop="name" label="食堂名称" min-width="150" />
        <el-table-column prop="school_name" label="学校" min-width="150" />
        <el-table-column prop="canteen_type_text" label="食堂类型" width="100" align="center" />
        <el-table-column prop="linkman" label="联系人" width="100" />
        <el-table-column prop="mobile" label="联系电话" width="120" />
        <el-table-column prop="is_audit_text" label="账号类型" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.is_audit === 1 ? 'success' : 'info'">
              {{ row.is_audit_text }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" align="center">
          <template #default="{ row }">
            <el-button
              v-if="row.is_audit === 0"
              type="success"
              link
              @click="handleSetAudit(row)"
            >
              设为主账号
            </el-button>
            <el-button
              v-if="row.is_audit === 1"
              type="warning"
              link
              @click="handleRemoveAudit(row)"
            >
              取消主账号
            </el-button>
            <el-button type="danger" link @click="handleRemoveCanteen(row)">
              移除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 添加食堂对话框 -->
      <el-dialog
        v-model="addCanteenDialogVisible"
        title="添加食堂"
        width="500px"
        append-to-body
      >
        <el-form :model="addCanteenForm" label-width="100px">
          <el-form-item label="选择食堂">
            <el-select
              v-model="addCanteenForm.canteen_id"
              placeholder="请选择食堂"
              filterable
              style="width: 100%"
            >
              <el-option
                v-for="item in availableCanteens"
                :key="item.id"
                :label="`${item.school_name} - ${item.name}`"
                :value="item.id"
              />
            </el-select>
          </el-form-item>
        </el-form>
        <template #footer>
          <el-button @click="addCanteenDialogVisible = false">取消</el-button>
          <el-button type="primary" :loading="addCanteenLoading" @click="handleAddCanteenSubmit">
            确定
          </el-button>
        </template>
      </el-dialog>

      <template #footer>
        <el-button @click="canteenDialogVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh, Plus } from '@element-plus/icons-vue'
import {
  getGroupList,
  getGroupDetail,
  createGroup,
  updateGroup,
  deleteGroup,
  getGroupCanteens,
  addCanteenToGroup,
  removeCanteenFromGroup,
  setCanteenAudit,
  removeCanteenAudit,
} from '@/api/modules/group'
import { getActiveCanteens } from '@/api/modules/canteen'

const searchForm = reactive({
  name: '',
  status: null,
})
const loading = ref(false)
const tableData = ref([])
const parentOptions = ref([])
const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0,
})

const formDialogVisible = ref(false)
const formType = ref('add')
const formRef = ref(null)
const submitLoading = ref(false)
const groupForm = reactive({
  id: null,
  name: '',
  pid: 0,
  code: '',
  status: 1,
})
const formRules = {
  name: [{ required: true, message: '请输入分组名称', trigger: 'blur' }],
}

const canteenDialogVisible = ref(false)
const currentGroup = ref({})
const canteenList = ref([])
const availableCanteens = ref([])
const addCanteenDialogVisible = ref(false)
const addCanteenLoading = ref(false)
const addCanteenForm = reactive({
  canteen_id: null,
})

async function fetchData() {
  loading.value = true
  try {
    const params = {
      ...searchForm,
      page: pagination.page,
      page_size: pagination.pageSize,
    }
    const { data } = await getGroupList(params)
    tableData.value = data.list || []
    pagination.total = data.total || 0
    parentOptions.value = tableData.value.filter(item => item.pid === 0)
  } catch (error) {
    console.error('获取分组列表失败:', error)
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  pagination.page = 1
  fetchData()
}

function handleReset() {
  Object.assign(searchForm, { name: '', status: null })
  handleSearch()
}

function handleAdd() {
  formType.value = 'add'
  Object.assign(groupForm, { id: null, name: '', pid: 0, code: '', status: 1 })
  formDialogVisible.value = true
}

async function handleEdit(row) {
  formType.value = 'edit'
  try {
    const { data } = await getGroupDetail(row.id)
    Object.assign(groupForm, {
      id: data.id,
      name: data.name,
      pid: data.pid || 0,
      code: data.code || '',
      status: data.status,
    })
    formDialogVisible.value = true
  } catch (error) {
    ElMessage.error('获取分组详情失败')
  }
}

async function handleSubmit() {
  try {
    await formRef.value.validate()
    submitLoading.value = true
    if (formType.value === 'add') {
      await createGroup(groupForm)
      ElMessage.success('创建成功')
    } else {
      await updateGroup(groupForm.id, groupForm)
      ElMessage.success('更新成功')
    }
    formDialogVisible.value = false
    fetchData()
  } catch (error) {
    if (error !== false) {
      ElMessage.error(error.message || '操作失败')
    }
  } finally {
    submitLoading.value = false
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm('确定要删除该分组吗？', '提示', { type: 'warning' })
    await deleteGroup(row.id)
    ElMessage.success('删除成功')
    fetchData()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(error.message || '删除失败')
    }
  }
}

async function handleCanteens(row) {
  currentGroup.value = row
  try {
    const { data } = await getGroupCanteens(row.id)
    canteenList.value = data || []
    canteenDialogVisible.value = true
  } catch (error) {
    ElMessage.error('获取分组食堂失败')
  }
}

async function handleAddCanteen() {
  try {
    const { data } = await getActiveCanteens()
    availableCanteens.value = data || []
    addCanteenForm.canteen_id = null
    addCanteenDialogVisible.value = true
  } catch (error) {
    ElMessage.error('获取食堂列表失败')
  }
}

async function handleAddCanteenSubmit() {
  if (!addCanteenForm.canteen_id) {
    ElMessage.warning('请选择食堂')
    return
  }
  try {
    addCanteenLoading.value = true
    await addCanteenToGroup(currentGroup.value.id, { canteen_id: addCanteenForm.canteen_id })
    ElMessage.success('添加成功')
    addCanteenDialogVisible.value = false
    const { data } = await getGroupCanteens(currentGroup.value.id)
    canteenList.value = data || []
  } catch (error) {
    ElMessage.error(error.message || '添加失败')
  } finally {
    addCanteenLoading.value = false
  }
}

async function handleRemoveCanteen(row) {
  try {
    await ElMessageBox.confirm('确定要移除该食堂吗？', '提示', { type: 'warning' })
    await removeCanteenFromGroup(currentGroup.value.id, row.id)
    ElMessage.success('移除成功')
    const { data } = await getGroupCanteens(currentGroup.value.id)
    canteenList.value = data || []
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(error.message || '移除失败')
    }
  }
}

async function handleSetAudit(row) {
  try {
    await ElMessageBox.confirm('确定要设置为该分组的主账号吗？', '提示', { type: 'warning' })
    await setCanteenAudit(currentGroup.value.id, row.id)
    ElMessage.success('设置成功')
    const { data } = await getGroupCanteens(currentGroup.value.id)
    canteenList.value = data || []
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(error.message || '设置失败')
    }
  }
}

async function handleRemoveAudit(row) {
  try {
    await ElMessageBox.confirm('确定要取消主账号吗？', '提示', { type: 'warning' })
    await removeCanteenAudit(currentGroup.value.id, row.id)
    ElMessage.success('取消成功')
    const { data } = await getGroupCanteens(currentGroup.value.id)
    canteenList.value = data || []
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(error.message || '取消失败')
    }
  }
}

function handleSizeChange(size) {
  pagination.pageSize = size
  fetchData()
}

function handlePageChange(page) {
  pagination.page = page
  fetchData()
}

onMounted(() => {
  fetchData()
})
</script>

<style lang="scss" scoped>
.group-list {
  .search-card {
    margin-bottom: 20px;
  }
  .el-pagination {
    margin-top: 20px;
    justify-content: flex-end;
  }
  .canteen-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
}
</style>