import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

interface Alumni {
  id: number
  first_name: string
  last_name: string
  graduation_year: number
  current_position?: string
  current_company?: string
  industry?: string
  city?: string
  state?: string
  country?: string
  profile_photo_path?: string
  latitude: number
  longitude: number
  profile_visibility: string
}

interface MapCluster {
  latitude: number
  longitude: number
  count: number
  industries?: string[]
  year_range?: {
    min: number
    max: number
  }
  countries?: string[]
}

interface MapFilters {
  graduation_year: number[]
  industry: string[]
  country: string[]
  state: string[]
  search?: string
  company?: string
  position?: string
  distance?: number
  visibility?: string[]
}

export const useAlumniMapStore = defineStore('alumniMap', () => {
  // State
  const alumni = ref<Alumni[]>([])
  const clusters = ref<MapCluster[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const filters = ref<MapFilters>({
    graduation_year: [],
    industry: [],
    country: [],
    state: [],
    search: '',
    company: '',
    position: '',
    distance: 50,
    visibility: ['public', 'alumni_only']
  })

  const selectedAlumni = ref<Alumni | null>(null)
  const mapBounds = ref<any>(null)
  const currentZoom = ref(4)

  // Getters
  const filteredAlumni = computed(() => {
    return alumni.value.filter(alumnus => {
      // Apply filters
      if (filters.value.graduation_year.length > 0 && 
          !filters.value.graduation_year.includes(alumnus.graduation_year)) {
        return false
      }

      if (filters.value.industry.length > 0 && 
          !filters.value.industry.includes(alumnus.industry || '')) {
        return false
      }

      if (filters.value.country.length > 0 && 
          !filters.value.country.includes(alumnus.country || '')) {
        return false
      }

      if (filters.value.state.length > 0 && 
          !filters.value.state.includes(alumnus.state || '')) {
        return false
      }

      if (filters.value.search && filters.value.search.length > 0) {
        const searchTerm = filters.value.search.toLowerCase()
        const fullName = `${alumnus.first_name} ${alumnus.last_name}`.toLowerCase()
        const company = (alumnus.current_company || '').toLowerCase()
        const position = (alumnus.current_position || '').toLowerCase()
        
        if (!fullName.includes(searchTerm) && 
            !company.includes(searchTerm) && 
            !position.includes(searchTerm)) {
          return false
        }
      }

      return true
    })
  })

  const alumniCount = computed(() => alumni.value.length)
  const filteredAlumniCount = computed(() => filteredAlumni.value.length)

  // Actions
  const setAlumni = (newAlumni: Alumni[]) => {
    alumni.value = newAlumni
  }

  const setClusters = (newClusters: MapCluster[]) => {
    clusters.value = newClusters
  }

  const setLoading = (isLoading: boolean) => {
    loading.value = isLoading
  }

  const setError = (errorMessage: string | null) => {
    error.value = errorMessage
  }

  const updateFilters = (newFilters: Partial<MapFilters>) => {
    filters.value = { ...filters.value, ...newFilters }
  }

  const clearFilters = () => {
    filters.value = {
      graduation_year: [],
      industry: [],
      country: [],
      state: [],
      search: '',
      company: '',
      position: '',
      distance: 50,
      visibility: ['public', 'alumni_only']
    }
  }

  const selectAlumni = (alumnus: Alumni | null) => {
    selectedAlumni.value = alumnus
  }

  const setMapBounds = (bounds: any) => {
    mapBounds.value = bounds
  }

  const setCurrentZoom = (zoom: number) => {
    currentZoom.value = zoom
  }

  const addAlumni = (newAlumnus: Alumni) => {
    const existingIndex = alumni.value.findIndex(a => a.id === newAlumnus.id)
    if (existingIndex >= 0) {
      alumni.value[existingIndex] = newAlumnus
    } else {
      alumni.value.push(newAlumnus)
    }
  }

  const removeAlumni = (alumniId: number) => {
    const index = alumni.value.findIndex(a => a.id === alumniId)
    if (index >= 0) {
      alumni.value.splice(index, 1)
    }
  }

  const updateAlumni = (updatedAlumnus: Alumni) => {
    const index = alumni.value.findIndex(a => a.id === updatedAlumnus.id)
    if (index >= 0) {
      alumni.value[index] = updatedAlumnus
    }
  }

  // API calls
  const fetchAlumniByLocation = async (bounds: any, mapFilters: MapFilters = filters.value) => {
    setLoading(true)
    setError(null)

    try {
      const response = await fetch('/api/alumni/map', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
          bounds,
          filters: mapFilters
        })
      })

      if (!response.ok) {
        throw new Error('Failed to fetch alumni data')
      }

      const data = await response.json()
      setAlumni(data)
      setMapBounds(bounds)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'An error occurred')
    } finally {
      setLoading(false)
    }
  }

  const fetchClusters = async (zoomLevel: number, bounds: any = null, mapFilters: MapFilters = filters.value) => {
    setLoading(true)
    setError(null)

    try {
      const response = await fetch('/api/alumni/map/clusters', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
          zoom_level: zoomLevel,
          bounds,
          filters: mapFilters
        })
      })

      if (!response.ok) {
        throw new Error('Failed to fetch cluster data')
      }

      const data = await response.json()
      setClusters(data)
      setCurrentZoom(zoomLevel)
      if (bounds) setMapBounds(bounds)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'An error occurred')
    } finally {
      setLoading(false)
    }
  }

  const fetchNearbyAlumni = async (radius: number = 25) => {
    setLoading(true)
    setError(null)

    try {
      const response = await fetch(`/api/alumni/nearby?radius=${radius}`)

      if (!response.ok) {
        throw new Error('Failed to fetch nearby alumni')
      }

      const data = await response.json()
      return data
    } catch (err) {
      setError(err instanceof Error ? err.message : 'An error occurred')
      return []
    } finally {
      setLoading(false)
    }
  }

  const searchAlumni = async (query: string, location?: { latitude: number, longitude: number, radius?: number }) => {
    setLoading(true)
    setError(null)

    try {
      const params = new URLSearchParams({ query })
      
      if (location) {
        params.append('latitude', location.latitude.toString())
        params.append('longitude', location.longitude.toString())
        if (location.radius) {
          params.append('radius', location.radius.toString())
        }
      }

      const response = await fetch(`/api/alumni/search?${params}`)

      if (!response.ok) {
        throw new Error('Failed to search alumni')
      }

      const data = await response.json()
      return data
    } catch (err) {
      setError(err instanceof Error ? err.message : 'An error occurred')
      return []
    } finally {
      setLoading(false)
    }
  }

  return {
    // State
    alumni,
    clusters,
    loading,
    error,
    filters,
    selectedAlumni,
    mapBounds,
    currentZoom,

    // Getters
    filteredAlumni,
    alumniCount,
    filteredAlumniCount,

    // Actions
    setAlumni,
    setClusters,
    setLoading,
    setError,
    updateFilters,
    clearFilters,
    selectAlumni,
    setMapBounds,
    setCurrentZoom,
    addAlumni,
    removeAlumni,
    updateAlumni,

    // API calls
    fetchAlumniByLocation,
    fetchClusters,
    fetchNearbyAlumni,
    searchAlumni
  }
})