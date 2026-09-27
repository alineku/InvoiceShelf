import type { Company } from './company'
import type { Currency } from './currency'
import type { Tax } from './tax'

export interface Unit {
  id: number
  name: string
  company_id: number
  company?: Company
}

export type WeightUnit = 'kg' | 'g'

export interface Item {
  id: number
  name: string
  description: string | null
  price: number
  sku: string | null
  brand: string | null
  packaging: string | null
  weight: number | null
  weight_unit: WeightUnit | null
  weight_in_kg: number | null
  pieces_per_carton: number | null
  unit_id: number | null
  company_id: number
  creator_id: number
  currency_id: number | null
  created_at: string
  updated_at: string
  tax_per_item: string | null
  formatted_created_at: string
  unit?: Unit
  company?: Company
  taxes?: Tax[]
  currency?: Currency
}
