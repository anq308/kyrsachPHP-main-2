export interface User {
  id: number;
  name: string;
  email: string;
  is_admin: boolean;
  role: 'client' | 'manager' | 'admin';
  is_manager: boolean;
  can_manage: boolean;
  created_at?: string;
}

export interface Motorcycle {
  id: number;
  brand: string;
  model: string;
  type: string;
  year: number;
  engine_capacity: number;
  power: number;
  price: number;
  description: string;
  image_url: string;
  is_available: boolean;
  stock_quantity: number;
  reserved_quantity: number;
  transmission?: string | null;
  cooling?: string | null;
  fuel_system?: string | null;
  weight?: number | null;
  tank_capacity?: number | null;
  views_count?: number;
}

export interface NewsItem {
  title: string;
  date: string;
  image: string;
  excerpt: string;
}

export interface CartItem {
  id: number | string;
  name: string;
  quantity: number;
  price: number;
  image: string;
  line_total: number;
}

export interface CartPayload {
  items: CartItem[];
  total: number;
  count: number;
}

export interface OrderItem {
  id: number;
  order_id: number;
  motorcycle_id: number | null;
  name: string;
  price: number;
  quantity: number;
}

export interface PickupPoint {
  id: number;
  name: string;
  address: string;
  phone: string | null;
  work_hours: string | null;
  is_active: boolean;
}

export interface Reservation {
  id: number;
  user_id: number | null;
  order_id: number;
  motorcycle_id: number;
  quantity: number;
  status: 'active' | 'released' | 'expired' | 'completed';
  expires_at: string | null;
  released_at: string | null;
  motorcycle?: Motorcycle | null;
}

export interface Payment {
  id: number;
  order_id: number;
  user_id: number | null;
  amount: number;
  method: 'cash_pickup' | 'card_pickup' | 'online_mock' | 'credit_request';
  status: 'pending' | 'paid' | 'failed' | 'refunded';
  transaction_id: string | null;
  paid_at: string | null;
  created_at: string;
  order?: Order | null;
  user?: User | null;
}

export interface ClientNotification {
  id: number;
  user_id: number;
  title: string;
  message: string;
  type: string;
  is_read: boolean;
  created_at: string;
}

export interface Order {
  id: number;
  user_id: number | null;
  name: string;
  phone: string;
  email: string | null;
  address: string | null;
  comment: string | null;
  total: number;
  status: 'new' | 'processing' | 'approved' | 'ready_for_pickup' | 'completed' | 'cancelled';
  payment_method?: 'cash_pickup' | 'card_pickup' | 'online_mock' | 'credit_request';
  payment_status?: 'pending' | 'paid' | 'failed' | 'refunded';
  pickup_point_id?: number | null;
  pickup_ready_at?: string | null;
  pickup_point?: PickupPoint | null;
  reservations?: Reservation[];
  payments?: Payment[];
  user?: User | null;
  items: OrderItem[];
  created_at: string;
}

export type SalesRequestStatus = 'new' | 'in_progress' | 'approved' | 'completed' | 'cancelled';
export type SalesRequestType = 'purchase' | 'consultation' | 'availability' | 'preorder' | 'test_drive';

export interface SalesRequest {
  id: number;
  user_id: number | null;
  motorcycle_id: number | null;
  name: string;
  phone: string;
  email: string | null;
  type: SalesRequestType;
  comment: string | null;
  status: SalesRequestStatus;
  motorcycle?: Motorcycle | null;
  user?: User | null;
  created_at: string;
}

export type ServiceRequestStatus = 'new' | 'confirmed' | 'in_service' | 'done' | 'cancelled';

export interface ServiceRequest {
  id: number;
  user_id: number | null;
  name: string;
  phone: string;
  email: string | null;
  motorcycle_model: string;
  service_type: string;
  preferred_date: string | null;
  comment: string | null;
  status: ServiceRequestStatus;
  user?: User | null;
  created_at: string;
}

export interface ContactMessage {
  id: number;
  name: string;
  phone: string | null;
  email: string | null;
  message: string;
  created_at: string;
}

export interface StatusHistory {
  id: number;
  entity_type: string;
  entity_id: number;
  old_status: string | null;
  new_status: string;
  user_id: number | null;
  comment: string | null;
  user?: User | null;
  created_at: string;
}
