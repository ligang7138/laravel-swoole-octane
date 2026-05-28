<template>
  <div class="sidebar" :class="{ 'is-collapsed': isCollapsed }">
    <!-- Logo -->
    <div class="sidebar-logo">
      <router-link to="/" class="logo-link">
        <img v-if="!isCollapsed" src="@/assets/images/logo.png" alt="logo" class="logo-img" />
        <el-icon v-else :size="24" color="#fff">
          <Shop />
        </el-icon>
        <span class="logo-title" v-if="!isCollapsed">教务采购系统</span>
      </router-link>
    </div>

    <!-- 首页菜单项 -->
    <div class="sidebar-home">
      <div
        class="menu-item home-item"
        :class="{ active: isHomeActive }"
        @click="goHome"
      >
        <el-icon :size="18"><HomeFilled /></el-icon>
        <span class="menu-text" v-if="!isCollapsed">首页</span>
      </div>
    </div>

    <!-- 菜单列表 -->
    <el-scrollbar class="sidebar-menu-wrapper">
      <div class="sidebar-menu">
        <div
          v-for="menu in menuList"
          :key="menu.id"
          class="menu-group"
        >
          <!-- 一级菜单标题 -->
          <div
            class="menu-title"
            :class="{ active: activeModule === menu.id }"
            @click="toggleMenu(menu)"
          >
            <el-icon :size="16"><List /></el-icon>
            <span class="title-text" v-if="!isCollapsed">{{ menu.module }}</span>
            <el-icon v-if="!isCollapsed" class="arrow-icon" :class="{ expanded: expandedMenu === menu.id }">
              <ArrowRight />
            </el-icon>
          </div>

          <!-- 二级菜单列表 -->
          <transition name="slide">
            <div
              v-show="expandedMenu === menu.id && !isCollapsed"
              class="menu-children"
            >
              <div
                v-for="child in menu.children"
                :key="child.id"
                class="menu-item child-item"
                :class="{ active: activePath === child.path }"
                @click="goToPage(child)"
              >
                <el-icon :size="14"><Right /></el-icon>
                <span class="menu-text">{{ child.func }}</span>
              </div>
            </div>
          </transition>
        </div>
      </div>
    </el-scrollbar>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Shop, HomeFilled, List, ArrowRight, Right } from '@element-plus/icons-vue'
import { useAppStore } from '@/stores/modules/app'
import { useUserStore } from '@/stores/modules/user'

const route = useRoute()
const router = useRouter()
const appStore = useAppStore()
const userStore = useUserStore()

const expandedMenu = ref(null)
const activeModule = ref(null)
const activePath = ref('')

const isCollapsed = computed(() => !appStore.sidebarOpened)
const isHomeActive = computed(() => route.path === '/dashboard' || route.path === '/')

// 菜单数据：从用户权限中获取
const menuList = computed(() => {
  // 模拟旧系统的菜单结构，实际应从后端接口获取
  // 这里先用静态数据演示，后续对接后端接口
  return buildMenuFromRoutes(userStore.addRoutes)
})

// 从路由配置构建菜单结构
function buildMenuFromRoutes(routes) {
  const menus = []
  let menuId = 1

  routes.forEach(route => {
    if (route.meta?.hidden) return

    // 跳过首页路由
    if (route.path === '/dashboard' || route.path === '/') return

    const menu = {
      id: menuId++,
      module: route.meta?.title || route.name,
      children: []
    }

    if (route.children && route.children.length > 0) {
      route.children.forEach(child => {
        if (child.meta?.hidden) return

        menu.children.push({
          id: menuId++,
          func: child.meta?.title || child.name,
          path: child.path ? `${route.path}/${child.path}` : route.path
        })
      })
    }

    if (menu.children.length > 0) {
      menus.push(menu)
    }
  })

  return menus
}

// 切换菜单展开/折叠
function toggleMenu(menu) {
  if (isCollapsed.value) return
  expandedMenu.value = expandedMenu.value === menu.id ? null : menu.id
  activeModule.value = menu.id
}

// 跳转首页
function goHome() {
  router.push('/dashboard')
}

// 跳转页面
function goToPage(item) {
  activePath.value = item.path
  router.push(item.path)
}

// 监听路由变化，自动展开对应菜单
watch(
  () => route.path,
  (path) => {
    activePath.value = path
    // 找到当前路由所属的菜单并展开
    menuList.value.forEach(menu => {
      const found = menu.children.some(child => child.path === path)
      if (found) {
        expandedMenu.value = menu.id
        activeModule.value = menu.id
      }
    })
  },
  { immediate: true }
)

onMounted(() => {
  // 初始化时展开第一个菜单
  if (menuList.value.length > 0) {
    expandedMenu.value = menuList.value[0].id
  }
})
</script>

<style lang="scss" scoped>
// 主题色彩变量 - 与旧系统保持一致
$sidebar-bg: #222d32;
$sidebar-active-bg: #1a2529;
$menu-text-color: #b8c7ce;
$menu-active-text-color: #fff;
$menu-hover-bg: #2c3b41;
$theme-color: #dc3251;

.sidebar {
  height: 100%;
  width: 170px;
  background-color: $sidebar-bg;
  display: flex;
  flex-direction: column;
  transition: width 0.3s ease;
  overflow: hidden;

  &.is-collapsed {
    width: 54px;

    .logo-title,
    .menu-text,
    .title-text,
    .arrow-icon {
      display: none;
    }

    .sidebar-logo {
      padding: 0;
      justify-content: center;
    }

    .menu-title {
      justify-content: center;
      padding: 12px 0;
    }

    .home-item {
      justify-content: center;
      padding-left: 0;
    }

    .menu-children {
      display: none;
    }
  }
}

// Logo 区域
.sidebar-logo {
  height: 50px;
  display: flex;
  align-items: center;
  padding: 0 15px;
  background-color: #1a2226;
  border-bottom: 1px solid #1a2226;

  .logo-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    width: 100%;
  }

  .logo-img {
    width: 32px;
    height: 32px;
    object-fit: contain;
  }

  .logo-title {
    color: $menu-active-text-color;
    font-size: 15px;
    font-weight: 600;
    margin-left: 10px;
    white-space: nowrap;
    overflow: hidden;
  }
}

// 首页菜单
.sidebar-home {
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  padding: 8px 0;
}

// 菜单项通用样式
.menu-item {
  display: flex;
  align-items: center;
  padding: 10px 15px;
  cursor: pointer;
  color: $menu-text-color;
  transition: all 0.2s ease;

  &:hover {
    background-color: $menu-hover-bg;
    color: $menu-active-text-color;
  }

  &.active {
    color: $menu-active-text-color;
    background-color: $theme-color;
  }

  .menu-text {
    margin-left: 10px;
    font-size: 14px;
    white-space: nowrap;
  }
}

// 首页菜单项
.home-item {
  padding-left: 18px;

  &.active {
    background-color: $theme-color;
  }
}

// 菜单滚动区域
.sidebar-menu-wrapper {
  flex: 1;
  overflow: hidden;

  :deep(.el-scrollbar__wrap) {
    overflow-x: hidden;
  }
}

// 菜单列表
.sidebar-menu {
  padding: 8px 0;
}

// 菜单组
.menu-group {
  margin-bottom: 2px;
}

// 一级菜单标题
.menu-title {
  display: flex;
  align-items: center;
  padding: 12px 15px;
  cursor: pointer;
  color: $menu-text-color;
  transition: all 0.2s ease;
  user-select: none;

  &:hover {
    background-color: $menu-hover-bg;
  }

  &.active {
    color: $menu-active-text-color;
  }

  .title-text {
    margin-left: 10px;
    font-size: 14px;
    font-weight: 500;
    flex: 1;
  }

  .arrow-icon {
    transition: transform 0.3s ease;

    &.expanded {
      transform: rotate(90deg);
    }
  }
}

// 二级菜单列表
.menu-children {
  background-color: rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

// 子菜单项
.child-item {
  padding-left: 35px;

  .menu-text {
    margin-left: 8px;
  }
}

// 展开/折叠动画
.slide-enter-active,
.slide-leave-active {
  transition: all 0.3s ease;
  max-height: 500px;
}

.slide-enter-from,
.slide-leave-to {
  max-height: 0;
  opacity: 0;
}

// 滚动条样式 - 与旧系统一致
:deep(::-webkit-scrollbar) {
  width: 5px;
  height: 5px;
}

:deep(::-webkit-scrollbar-track) {
  display: none;
}

:deep(::-webkit-scrollbar-thumb) {
  background: $theme-color;
  border-radius: 4px;
}
</style>
