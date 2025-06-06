import * as React from "react"

interface BadgeProps extends React.HTMLAttributes<HTMLDivElement> {
  variant?: 'default' | 'secondary' | 'destructive' | 'outline'
}

function Badge({ className = '', variant = 'default', ...props }: BadgeProps) {
  const baseClasses = "inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors"
  
  const variantClasses = {
    default: "border-transparent bg-blue-500 text-white hover:bg-blue-600",
    secondary: "border-transparent bg-gray-200 text-gray-900 hover:bg-gray-300",
    destructive: "border-transparent bg-red-500 text-white hover:bg-red-600",
    outline: "text-gray-700 border-gray-300 bg-white hover:bg-gray-50",
  }
  
  const classes = `${baseClasses} ${variantClasses[variant]} ${className}`.trim()
  
  return (
    <div className={classes} {...props} />
  )
}

export { Badge } 