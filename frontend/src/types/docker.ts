export interface DockerContainer {
  id: string
  name: string
  image: string
  status: string
  state: string
  created: string
}

export interface DockerImage {
  Id?: string
  ParentId?: string
  RepoTags?: string[]
  RepoDigests?: string[]
  Created?: number
  Size?: number
  VirtualSize?: number
  SharedSize?: number
  Labels?: Record<string, string> | null
  Containers?: number
} 