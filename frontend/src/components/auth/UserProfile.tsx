import React, { useState } from 'react';
import { useAuth } from '@/hooks/useAuth';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { LogOut, User, Shield, ChevronDown } from 'lucide-react';

export const UserProfile: React.FC = () => {
  const { user, logout, isAuthenticated } = useAuth();
  const [isDropdownOpen, setIsDropdownOpen] = useState<boolean>(false);

  if (!isAuthenticated || !user) {
    return null;
  }

  const handleLogout = (): void => {
    logout();
    setIsDropdownOpen(false);
  };

  const getInitials = (name: string): string => {
    return name
      .split(' ')
      .map(word => word.charAt(0))
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  const isAdmin = user.roles.includes('ROLE_ADMIN');

  return (
    <div className="relative">
      <button
        onClick={() => setIsDropdownOpen(!isDropdownOpen)}
        className="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-100 transition-colors"
      >
        <Avatar className="h-8 w-8">
          <AvatarImage src="/api/placeholder/32/32" alt={user.name} />
          <AvatarFallback className="bg-gradient-to-r from-blue-500 to-blue-600 text-white text-sm font-medium">
            {getInitials(user.name)}
          </AvatarFallback>
        </Avatar>
        <div className="hidden md:block text-left">
          <p className="text-sm font-medium text-gray-900">{user.name}</p>
          <p className="text-xs text-gray-500">{user.email}</p>
        </div>
        <ChevronDown className={`h-4 w-4 text-gray-400 transition-transform ${
          isDropdownOpen ? 'rotate-180' : ''
        }`} />
      </button>

      {/* Dropdown Menu */}
      {isDropdownOpen && (
        <>
          {/* Backdrop */}
          <div 
            className="fixed inset-0 z-10" 
            onClick={() => setIsDropdownOpen(false)}
          />
          
          {/* Dropdown Content */}
          <Card className="absolute right-0 top-full mt-2 w-64 z-20 shadow-lg border">
            <CardContent className="p-4">
              {/* User Info */}
              <div className="flex items-center gap-3 pb-3 border-b border-gray-100">
                <Avatar className="h-10 w-10">
                  <AvatarImage src="/api/placeholder/40/40" alt={user.name} />
                  <AvatarFallback className="bg-gradient-to-r from-blue-500 to-blue-600 text-white font-medium">
                    {getInitials(user.name)}
                  </AvatarFallback>
                </Avatar>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-gray-900 truncate">
                    {user.name}
                  </p>
                  <p className="text-xs text-gray-500 truncate">
                    {user.email}
                  </p>
                  <div className="flex items-center gap-1 mt-1">
                    {isAdmin ? (
                      <div className="flex items-center gap-1 px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs">
                        <Shield className="h-3 w-3" />
                        Admin
                      </div>
                    ) : (
                      <div className="flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs">
                        <User className="h-3 w-3" />
                        User
                      </div>
                    )}
                  </div>
                </div>
              </div>

              {/* User Stats */}
              <div className="py-3 border-b border-gray-100">
                <div className="grid grid-cols-2 gap-4 text-center">
                  <div>
                    <p className="text-lg font-semibold text-gray-900">
                      {user.id}
                    </p>
                    <p className="text-xs text-gray-500">User ID</p>
                  </div>
                  <div>
                    <p className="text-lg font-semibold text-gray-900">
                      {user.roles.length}
                    </p>
                    <p className="text-xs text-gray-500">Roles</p>
                  </div>
                </div>
              </div>

              {/* Actions */}
              <div className="pt-3">
                <Button
                  onClick={handleLogout}
                  variant="outline"
                  className="w-full justify-start gap-2 text-red-600 border-red-200 hover:bg-red-50 hover:border-red-300"
                >
                  <LogOut className="h-4 w-4" />
                  Sign Out
                </Button>
              </div>
            </CardContent>
          </Card>
        </>
      )}
    </div>
  );
}; 