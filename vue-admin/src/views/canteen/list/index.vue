<template>
  <div class="canteen-list">
    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :model="searchForm" inline>
        <el-form-item label="学校">
          <el-select v-model="searchForm.school_id" placeholder="请选择学校" clearable style="width: 200px">
            <el-option v-for="item in schoolList" :key="item.id" :label="item.school_name" :value="item.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="食堂名称">
          <el-input v-model="searchForm.keyword" placeholder="请输入食堂名称" clearable @keyup.enter="handleSearch" />
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
          <span>食堂列表</span>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>
            新增食堂
          </el-button>
        </div>
      </template>

      <!-- 表格 -->
      <el-table v-loading="loading" :data="tableData" border stripe>
        <el-table-column prop="id" label="ID" width="80" align="center" />
        <el-table-column prop="school_name" label="所属学校" min-width="150" />
        <el-table-column prop="canteen_name" label="食堂名称" min-width="150" />
        <el-table-column prop="contact_name" label="联系人" width="100" align="center" />
        <el-table-column prop="contact_phone" label="联系电话" width="130" align="center" />
        <el-table-column prop="address" label="地址" min-width="200" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">{{ row.status_text }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" align="center" />
        <el-table-column label="操作" width="150" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
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
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="500px" :close-on-click-modal="false" @closed="handleDialogClosed">
      <el-form ref="formRef" :model="formData" :rules="formRules" label-width="100px">
        <el-form-item label="所属学校" prop="school_id">
          <el-select v-model="formData.school_id" placeholder="请选择学校" style="width: 100%">
            <el-option v-for="item in schoolList" :key="item.id" :label="item.school_name" :value="item.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="食堂名称" prop="canteen_name">
          <el-input v-model="formData.canteen_name" placeholder="请输入食堂名称" maxlength="255" />
        </el-form-item>
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
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="formData.status">
            <el-radio :value="1">已激活</el-radio>
            <el-radio :value="0">未激活</el-radio>
          </el-radio-group>
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
import { useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh, Plus } from '@element-plus/icons-vue'
import { getCanteenList, getCanteenDetail, createCanteen, updateCanteen, deleteCanteen, getActiveSchools } from '@/api/modules/school'

const route = useRoute()

const searchForm = reactive({ keyword: '', school_id: null })
const loading = ref(false)
const tableData = ref([])
const schoolList = ref([])
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })

const dialogVisible = ref(false)
const dialogTitle = computed(() => (formData.id ? '编辑食堂' : '新增食堂'))
const formRef = ref()
const submitLoading = ref(false)

const formData = reactive({
  id: null,
  school_id: null,
  canteen_name: '',
  contact_name: '',
  contact_phone: '',
  address: '',
  status: 1,
  remark: '',
})

const formRules = {
  school_id: [{ required: true, message: '请选择学校', trigger: 'change' }],
  canteen_name: [{ required: true, message: '请输入食堂名称', trigger: 'blur' }],
}

async function fetchSchools() {
  try {
    const { data } = await getActiveSchools()
    schoolList.value = data || []
  } catch (error) {
    console.error('获取学校列表失败:', error)
  }
}

async function fetchData() {
  loading.value = true
  try {
    // 如果有选择学校，则获取该学校的食堂列表
    if (searchForm.school_id) {
      const { data } = await getCanteenList(searchForm.school_id, {
        keyword: searchForm.keyword,
        page: pagination.page,
        page_size: pagination.pageSize,
      })
      tableData.value = data.list || data || []
      pagination.total = data.total || tableData.value.length
    } else {
      // 没有选择学校时，获取所有学校的食堂（需要后端支持）
      // 暂时显示空列表或获取第一个学校的食堂
      if (schoolList.value.length > 0) {
        const { data } = await getCanteenList(schoolList.value[0].id, {
          page: pagination.page,
          page_size: pagination.pageSize,
        })
        tableData.value = data.list || data || []
        pagination.total = data.total || tableData.value.length
      } else {
        tableData.value = []
        pagination.total = 0
      }
    }
  } catch (error) {
    console.error('获取食堂列表失败:', error)
    tableData.value = []
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  pagination.page = 1
  fetchData()
}

function handleReset() {
  Object.assign(searchForm, { keyword: '', school_id: null })
  handleSearch()
}

function handleAdd() {
  formData.id = null
  if (searchForm.school_id) {
    formData.school_id = searchForm.school_id
  }
  dialogVisible.value = true
}

async function handleEdit(row) {
  try {
    const { data } = await getCanteenDetail(row.school_id, row.id)
    Object.assign(formData, data)
    dialogVisible.value = true
  } catch (error) {
    ElMessage.error('获取食堂详情失败')
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm('确定要删除该食堂吗？', '提示', { type: 'warning' })
    await deleteCanteen(row.school_id, row.id)
    ElMessage.success('删除成功')
    fetchData()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

async function handleSubmit() {
  try {
    await formRef.value.validate()
    submitLoading.value = true

    const data = { ...formData }
    if (data.id) {
      await updateCanteen(data.school_id, data.id, data)
      ElMessage.success('更新成功')
    } else {
      await createCanteen(data.school_id, data)
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
    id: null, school_id: null, canteen_name: '', contact_name: '', contact_phone: '', address: '', status: 1, remark: '',
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
  // 从路由参数获取学校ID
  if (route.query.school_id) {
    searchForm.school_id = parseInt(route.query.school_id)
  }
  fetchSchools()
  fetchData()
})
</script>

<style lang="scss" scoped>
.canteen-list {
  .search-card { margin-bottom: 20px; }
  .table-card .card-header { display: flex; justify-content: space-between; align-items: center; }
  .el-pagination { margin-top: 20px; justify-content: flex-end; }
}
</style>