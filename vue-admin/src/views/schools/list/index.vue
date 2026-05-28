<template>
  <div class="school-list">
    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :model="searchForm" inline>
        <el-form-item label="学校名称">
          <el-input v-model="searchForm.keyword" placeholder="请输入学校名称/编码/联系人" clearable @keyup.enter="handleSearch" />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="请选择状态" clearable style="width: 120px">
            <el-option label="已激活" :value="1" />
            <el-option label="未激活" :value="0" />
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
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 操作栏 -->
    <el-card class="table-card">
      <template #header>
        <div class="card-header">
          <span>学校列表</span>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>
            新增学校
          </el-button>
        </div>
      </template>

      <!-- 表格 -->
      <el-table v-loading="loading" :data="tableData" border stripe>
        <el-table-column prop="id" label="ID" width="80" align="center" />
        <el-table-column prop="school_name" label="学校名称" min-width="200" show-overflow-tooltip />
        <el-table-column prop="school_code" label="学校编码" width="120" align="center" />
        <el-table-column prop="contact_name" label="联系人" width="100" align="center" />
        <el-table-column prop="contact_phone" label="联系电话" width="130" align="center" />
        <el-table-column prop="address" label="地址" min-width="200" show-overflow-tooltip />
        <el-table-column prop="canteen_count" label="食堂数量" width="100" align="center" />
        <el-table-column prop="status" label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">{{ row.status_text }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" align="center" />
        <el-table-column label="操作" width="180" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
            <el-button type="info" link @click="handleViewCanteens(row)">食堂</el-button>
            <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
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
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="600px" :close-on-click-modal="false" @closed="handleDialogClosed">
      <el-form ref="formRef" :model="formData" :rules="formRules" label-width="100px">
        <el-form-item label="学校名称" prop="school_name">
          <el-input v-model="formData.school_name" placeholder="请输入学校名称" maxlength="255" />
        </el-form-item>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="学校编码" prop="school_code">
              <el-input v-model="formData.school_code" placeholder="请输入学校编码" maxlength="50" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="状态" prop="status">
              <el-radio-group v-model="formData.status">
                <el-radio :value="1">已激活</el-radio>
                <el-radio :value="0">未激活</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="联系人" prop="contact_name">
              <el-input v-model="formData.contact_name" placeholder="请输入联系人" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="联系电话" prop="contact_phone">
              <el-input v-model="formData.contact_phone" placeholder="请输入联系电话" maxlength="20" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="地址" prop="address">
          <el-input v-model="formData.address" placeholder="请输入地址" maxlength="500" />
        </el-form-item>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="统一信用代码" prop="credit_code">
              <el-input v-model="formData.credit_code" placeholder="请输入统一信用代码" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="开户银行" prop="bank_name">
              <el-input v-model="formData.bank_name" placeholder="请输入开户银行" maxlength="100" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="银行账号" prop="bank_account">
          <el-input v-model="formData.bank_account" placeholder="请输入银行账号" maxlength="50" />
        </el-form-item>
        <el-form-item label="备注" prop="remark">
          <el-input v-model="formData.remark" type="textarea" :rows="3" placeholder="请输入备注" maxlength="500" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh, Plus } from '@element-plus/icons-vue'
import { getSchoolList, getSchoolDetail, createSchool, updateSchool, deleteSchool } from '@/api/modules/school'

const router = useRouter()

const searchForm = reactive({ keyword: '', status: null })
const loading = ref(false)
const tableData = ref([])
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })

const dialogVisible = ref(false)
const dialogTitle = computed(() => (formData.id ? '编辑学校' : '新增学校'))
const formRef = ref()
const submitLoading = ref(false)

const formData = reactive({
  id: null,
  school_name: '',
  school_code: '',
  contact_name: '',
  contact_phone: '',
  address: '',
  status: 1,
  credit_code: '',
  bank_name: '',
  bank_account: '',
  remark: '',
})

const formRules = {
  school_name: [{ required: true, message: '请输入学校名称', trigger: 'blur' }],
  contact_name: [{ required: true, message: '请输入联系人', trigger: 'blur' }],
  contact_phone: [{ required: true, message: '请输入联系电话', trigger: 'blur' }],
}

async function fetchData() {
  loading.value = true
  try {
    const { data } = await getSchoolList({ ...searchForm, page: pagination.page, page_size: pagination.pageSize })
    tableData.value = data.list || []
    pagination.total = data.total || 0
  } catch (error) {
    console.error('获取学校列表失败:', error)
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  pagination.page = 1
  fetchData()
}

function handleReset() {
  Object.assign(searchForm, { keyword: '', status: null })
  handleSearch()
}

function handleAdd() {
  formData.id = null
  dialogVisible.value = true
}

async function handleEdit(row) {
  try {
    const { data } = await getSchoolDetail(row.id)
    Object.assign(formData, data)
    dialogVisible.value = true
  } catch (error) {
    ElMessage.error('获取学校详情失败')
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm('确定要删除该学校吗？', '提示', { type: 'warning' })
    await deleteSchool(row.id)
    ElMessage.success('删除成功')
    fetchData()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(error.message || '删除失败')
    }
  }
}

function handleViewCanteens(row) {
  router.push({ path: '/canteens/list', query: { school_id: row.id } })
}

async function handleSubmit() {
  try {
    await formRef.value.validate()
    submitLoading.value = true

    const data = { ...formData }
    if (data.id) {
      await updateSchool(data.id, data)
      ElMessage.success('更新成功')
    } else {
      await createSchool(data)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    fetchData()
  } catch (error) {
    console.error('提交失败:', error)
  } finally {
    submitLoading.value = false
  }
}

function handleDialogClosed() {
  formRef.value?.resetFields()
  Object.assign(formData, {
    id: null, school_name: '', school_code: '', contact_name: '', contact_phone: '',
    address: '', status: 1, credit_code: '', bank_name: '', bank_account: '', remark: '',
  })
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
.school-list {
  .search-card { margin-bottom: 20px; }
  .table-card .card-header { display: flex; justify-content: space-between; align-items: center; }
  .el-pagination { margin-top: 20px; justify-content: flex-end; }
}
</style>