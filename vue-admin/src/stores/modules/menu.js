import { defineStore } from 'pinia'

export const useMenuStore = defineStore('menu', {
  state: () => ({
    modules: [],
    rawMenuTree: [],
  }),
  actions: {
    setMenus(menuTree = []) {
      this.rawMenuTree = menuTree
      this.modules = menuTree.map((item) => item.module).filter(Boolean)
    },
    clearMenus() {
      this.modules = []
      this.rawMenuTree = []
    },
  },
})
