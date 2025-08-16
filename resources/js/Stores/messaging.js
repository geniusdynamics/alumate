import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import webSocketService from '@/Services/WebSocketService'

export const useMessagingStore = defineStore('messaging', () => {
  // State
  const conversations = ref([])
  const messages = ref({}) // { conversationId: [messages] }
  const typingUsers = ref({}) // { conversationId: [users] }
  const onlineUsers = ref([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const hasMoreConversations = ref(true)
  const hasMoreMessagesMap = ref({}) // { conversationId: boolean }

  // Getters
  const unreadConversations = computed(() => 
    conversations.value.filter(c => c.unread_count > 0)
  )

  const pinnedConversations = computed(() => 
    conversations.value.filter(c => c.is_pinned)
  )

  const directConversations = computed(() => 
    conversations.value.filter(c => c.type === 'direct')
  )

  const groupConversations = computed(() => 
    conversations.value.filter(c => c.type === 'group' || c.type === 'circle')
  )

  const getConversationMessages = (conversationId) => {
    return messages.value[conversationId] || []
  }

  const hasMoreMessages = (conversationId) => {
    return hasMoreMessagesMap.value[conversationId] !== false
  }

  const getTypingUsers = (conversationId) => {
    return typingUsers.value[conversationId] || []
  }

  // Actions
  const loadConversations = async (page = 1) => {
    try {
      loading.value = true
      const response = await axios.get('/api/conversations', {
        params: { page, per_page: 20 }
      })

      if (page === 1) {
        conversations.value = response.data.data.data
      } else {
        conversations.value.push(...response.data.data.data)
      }

      hasMoreConversations.value = response.data.data.has_more_pages
      
      // Set up real-time listeners for each conversation
      conversations.value.forEach(conversation => {
        setupConversationListeners(conversation.id)
      })

      return response.data
    } catch (error) {
      console.error('Failed to load conversations:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  const loadMoreConversations = async () => {
    if (!hasMoreConversations.value || loading.value) return
    
    const currentPage = Math.ceil(conversations.value.length / 20) + 1
    return loadConversations(currentPage)
  }

  const loadMessages = async (conversationId, page = 1) => {
    try {
      const response = await axios.get(`/api/conversations/${conversationId}`, {
        params: { page, per_page: 50 }
      })

      const messagesList = response.data.data.data.reverse() // Reverse to show oldest first

      if (page === 1) {
        messages.value[conversationId] = messagesList
      } else {
        messages.value[conversationId] = [...messagesList, ...messages.value[conversationId]]
      }

      hasMoreMessagesMap.value[conversationId] = response.data.data.has_more_pages

      return response.data
    } catch (error) {
      console.error('Failed to load messages:', error)
      throw error
    }
  }

  const loadMoreMessages = async (conversationId) => {
    if (!hasMoreMessages(conversationId)) return
    
    const currentMessages = messages.value[conversationId] || []
    const currentPage = Math.ceil(currentMessages.length / 50) + 1
    return loadMessages(conversationId, currentPage)
  }

  const sendMessage = async (messageData) => {
    try {
      const response = await axios.post('/api/messages', messageData)
      
      // Add message to local state immediately for optimistic updates
      const conversationId = messageData.conversation_id
      if (!messages.value[conversationId]) {
        messages.value[conversationId] = []
      }
      
      // The real message will come through WebSocket, but add optimistic version
      const optimisticMessage = {
        ...response.data.message,
        sending: true
      }
      
      messages.value[conversationId].push(optimisticMessage)
      
      // Update conversation's last message
      updateConversationLastMessage(conversationId, optimisticMessage)

      return response.data
    } catch (error) {
      console.error('Failed to send message:', error)
      throw error
    }
  }

  const markMessageAsRead = async (messageId) => {
    try {
      await axios.post(`/api/messages/${messageId}/read`)
    } catch (error) {
      console.error('Failed to mark message as read:', error)
      throw error
    }
  }

  const markConversationAsRead = async (conversationId) => {
    try {
      await axios.post(`/api/conversations/${conversationId}/read`)
      
      // Update local state
      const conversation = conversations.value.find(c => c.id === conversationId)
      if (conversation) {
        conversation.unread_count = 0
      }
      
      updateUnreadCount()
    } catch (error) {
      console.error('Failed to mark conversation as read:', error)
      throw error
    }
  }

  const sendTypingIndicator = async (conversationId, isTyping) => {
    try {
      await axios.post('/api/messages/typing', {
        conversation_id: conversationId,
        is_typing: isTyping
      })
    } catch (error) {
      console.error('Failed to send typing indicator:', error)
    }
  }

  const searchMessages = async (query, conversationId = null) => {
    try {
      const response = await axios.get('/api/messages/search', {
        params: { query, conversation_id: conversationId }
      })
      return response.data
    } catch (error) {
      console.error('Failed to search messages:', error)
      throw error
    }
  }

  const editMessage = async (messageId, content) => {
    try {
      const response = await axios.put(`/api/messages/${messageId}`, { content })
      
      // Update local state
      Object.keys(messages.value).forEach(conversationId => {
        const messageIndex = messages.value[conversationId].findIndex(m => m.id === messageId)
        if (messageIndex !== -1) {
          messages.value[conversationId][messageIndex] = response.data.message
        }
      })

      return response.data
    } catch (error) {
      console.error('Failed to edit message:', error)
      throw error
    }
  }

  const deleteMessage = async (messageId) => {
    try {
      await axios.delete(`/api/messages/${messageId}`)
      
      // Remove from local state
      Object.keys(messages.value).forEach(conversationId => {
        messages.value[conversationId] = messages.value[conversationId].filter(m => m.id !== messageId)
      })
    } catch (error) {
      console.error('Failed to delete message:', error)
      throw error
    }
  }

  const createDirectConversation = async (userId) => {
    try {
      const response = await axios.post('/api/conversations/direct', { user_id: userId })
      
      // Add to conversations list if not already there
      const existingIndex = conversations.value.findIndex(c => c.id === response.data.conversation.id)
      if (existingIndex === -1) {
        conversations.value.unshift(response.data.conversation)
      }

      return response.data.conversation
    } catch (error) {
      console.error('Failed to create direct conversation:', error)
      throw error
    }
  }

  const createGroupConversation = async (participantIds, title, description) => {
    try {
      const response = await axios.post('/api/conversations/group', {
        participant_ids: participantIds,
        title,
        description
      })
      
      // Add to conversations list
      conversations.value.unshift(response.data.conversation)
      
      return response.data.conversation
    } catch (error) {
      console.error('Failed to create group conversation:', error)
      throw error
    }
  }

  const createCircleConversation = async (circleId, title) => {
    try {
      const response = await axios.post('/api/conversations/circle', {
        circle_id: circleId,
        title
      })
      
      // Add to conversations list
      conversations.value.unshift(response.data.conversation)
      
      return response.data.conversation
    } catch (error) {
      console.error('Failed to create circle conversation:', error)
      throw error
    }
  }

  const togglePinConversation = async (conversationId) => {
    try {
      const response = await axios.post(`/api/conversations/${conversationId}/pin`)
      
      // Update local state
      const conversation = conversations.value.find(c => c.id === conversationId)
      if (conversation) {
        conversation.is_pinned = response.data.is_pinned
      }

      return response.data
    } catch (error) {
      console.error('Failed to toggle pin conversation:', error)
      throw error
    }
  }

  const toggleMuteConversation = async (conversationId) => {
    try {
      const response = await axios.post(`/api/conversations/${conversationId}/mute`)
      
      // Update local state
      const conversation = conversations.value.find(c => c.id === conversationId)
      if (conversation) {
        conversation.is_muted = response.data.is_muted
      }

      return response.data
    } catch (error) {
      console.error('Failed to toggle mute conversation:', error)
      throw error
    }
  }

  const archiveConversation = async (conversationId) => {
    try {
      await axios.post(`/api/conversations/${conversationId}/archive`)
      
      // Remove from local state
      conversations.value = conversations.value.filter(c => c.id !== conversationId)
    } catch (error) {
      console.error('Failed to archive conversation:', error)
      throw error
    }
  }

  const addParticipant = async (conversationId, userId, role = 'participant') => {
    try {
      const response = await axios.post(`/api/conversations/${conversationId}/participants`, {
        user_id: userId,
        role
      })
      
      // Update local conversation participants
      const conversation = conversations.value.find(c => c.id === conversationId)
      if (conversation) {
        conversation.participants.push(response.data.participant.user)
      }

      return response.data
    } catch (error) {
      console.error('Failed to add participant:', error)
      throw error
    }
  }

  const removeParticipant = async (conversationId, userId) => {
    try {
      await axios.delete(`/api/conversations/${conversationId}/participants/${userId}`)
      
      // Update local conversation participants
      const conversation = conversations.value.find(c => c.id === conversationId)
      if (conversation) {
        conversation.participants = conversation.participants.filter(p => p.id !== userId)
      }
    } catch (error) {
      console.error('Failed to remove participant:', error)
      throw error
    }
  }

  const leaveConversation = async (conversationId) => {
    try {
      await axios.post(`/api/conversations/${conversationId}/leave`)
      
      // Remove from local state
      conversations.value = conversations.value.filter(c => c.id !== conversationId)
    } catch (error) {
      console.error('Failed to leave conversation:', error)
      throw error
    }
  }

  const loadUnreadCount = async () => {
    try {
      const response = await axios.get('/api/messages/unread-count')
      unreadCount.value = response.data.unread_count
      return response.data
    } catch (error) {
      console.error('Failed to load unread count:', error)
      throw error
    }
  }

  // WebSocket event handlers
  const setupConversationListeners = (conversationId) => {
    // Listen for new messages
    webSocketService.private(`conversation.${conversationId}`)?.listen('message.sent', (data) => {
      handleNewMessage(data.message)
    })

    // Listen for message read receipts
    webSocketService.private(`conversation.${conversationId}`)?.listen('message.read', (data) => {
      handleMessageRead(data)
    })

    // Listen for typing indicators
    webSocketService.private(`conversation.${conversationId}`)?.listen('user.typing', (data) => {
      handleTypingIndicator(data)
    })
  }

  const handleNewMessage = (message) => {
    const conversationId = message.conversation_id
    
    // Add message to local state
    if (!messages.value[conversationId]) {
      messages.value[conversationId] = []
    }
    
    // Remove optimistic message if it exists
    messages.value[conversationId] = messages.value[conversationId].filter(m => !m.sending)
    
    // Add the real message
    messages.value[conversationId].push(message)
    
    // Update conversation's last message and unread count
    updateConversationLastMessage(conversationId, message)
    updateUnreadCount()
  }

  const handleMessageRead = (data) => {
    // Update read receipts for the message
    const conversationId = data.conversation_id
    const messageId = data.message_id
    
    if (messages.value[conversationId]) {
      const message = messages.value[conversationId].find(m => m.id === messageId)
      if (message) {
        if (!message.reads) message.reads = []
        message.reads.push({
          user: data.user,
          read_at: data.read_at
        })
      }
    }
  }

  const handleTypingIndicator = (data) => {
    const conversationId = data.conversation_id
    
    if (!typingUsers.value[conversationId]) {
      typingUsers.value[conversationId] = []
    }
    
    const existingIndex = typingUsers.value[conversationId].findIndex(u => u.id === data.user.id)
    
    if (data.is_typing) {
      if (existingIndex === -1) {
        typingUsers.value[conversationId].push(data.user)
      }
    } else {
      if (existingIndex !== -1) {
        typingUsers.value[conversationId].splice(existingIndex, 1)
      }
    }
  }

  const updateConversationLastMessage = (conversationId, message) => {
    const conversation = conversations.value.find(c => c.id === conversationId)
    if (conversation) {
      conversation.latest_message = message
      conversation.last_message_at = message.created_at
      
      // Move conversation to top of list
      const index = conversations.value.indexOf(conversation)
      if (index > 0) {
        conversations.value.splice(index, 1)
        conversations.value.unshift(conversation)
      }
    }
  }

  const updateUnreadCount = () => {
    unreadCount.value = conversations.value.reduce((total, conversation) => {
      return total + (conversation.unread_count || 0)
    }, 0)
  }

  const joinConversation = (conversationId) => {
    setupConversationListeners(conversationId)
  }

  const leaveConversationChannel = (conversationId) => {
    webSocketService.leave(`conversation.${conversationId}`)
  }

  // Initialize store
  const initialize = async () => {
    try {
      await Promise.all([
        loadConversations(),
        loadUnreadCount()
      ])
    } catch (error) {
      console.error('Failed to initialize messaging store:', error)
    }
  }

  return {
    // State
    conversations,
    messages,
    typingUsers,
    onlineUsers,
    unreadCount,
    loading,
    hasMoreConversations,

    // Getters
    unreadConversations,
    pinnedConversations,
    directConversations,
    groupConversations,
    getConversationMessages,
    hasMoreMessages,
    getTypingUsers,

    // Actions
    loadConversations,
    loadMoreConversations,
    loadMessages,
    loadMoreMessages,
    sendMessage,
    markMessageAsRead,
    markConversationAsRead,
    sendTypingIndicator,
    searchMessages,
    editMessage,
    deleteMessage,
    createDirectConversation,
    createGroupConversation,
    createCircleConversation,
    togglePinConversation,
    toggleMuteConversation,
    archiveConversation,
    addParticipant,
    removeParticipant,
    leaveConversation,
    loadUnreadCount,
    joinConversation,
    leaveConversation: leaveConversationChannel,
    initialize
  }
})